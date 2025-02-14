<?php declare(strict_types=1);

namespace Selpol\Controller\Admin\Intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Controller\Request\Admin\Intercom\IntercomDeviceCallRequest;
use Selpol\Controller\Request\Admin\Intercom\IntercomDeviceLevelRequest;
use Selpol\Controller\Request\Admin\Intercom\IntercomDeviceOpenRequest;
use Selpol\Controller\Request\Admin\Intercom\IntercomDevicePasswordRequest;
use Selpol\Controller\Request\Admin\Intercom\IntercomDeviceResetRequest;
use Selpol\Device\Ip\Intercom\Setting\Sip\Sip;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Feature\Sip\SipFeature;
use Selpol\Framework\Http\Uri;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method\Get;
use Selpol\Framework\Router\Attribute\Method\Post;
use Selpol\Task\Tasks\Intercom\IntercomConfigureTask;
use Throwable;

/**
 * Домофон-Устройство
 */
#[Controller('/admin/intercom/device')]
readonly class IntercomDeviceController extends AdminRbtController
{
    /**
     * Получить информацию об домофоне
     * @param int $id Идентификатор домофона
     */
    #[Get('/{id}')]
    public function info(int $id): ResponseInterface
    {
        $device = intercom($id);

        if (!$device) {
            return self::error('Не удалось найти домофон', 404);
        }

        if (!$device->ping()) {
            return self::error('Домофон не доступен', 400);
        }

        $info = $device->getSysInfo();

        $device->intercom->device_id = $info->deviceId;
        $device->intercom->device_model = $info->deviceModel;
        $device->intercom->device_software_version = $info->softwareVersion;
        $device->intercom->device_hardware_version = $info->hardwareVersion;

        $device->intercom->update();

        $info = [
            'DeviceID' => $info->deviceId,
            'DeviceModel' => $info->deviceModel,
            'HardwareVersion' => $info->hardwareVersion,
            'SoftwareVersion' => $info->softwareVersion,
        ];

        $info['cms'] = explode(',', $device->resolver->string('cms.value', ''));
        $info['output'] = $device->resolver->int('output', 1);

        return self::success($info);
    }

    /**
     * Позвонить с домофона или сбросить звонок
     */
    #[Post('/call/{id}')]
    public function call(IntercomDeviceCallRequest $request): ResponseInterface
    {
        $device = intercom($request->id);

        if (!$device) {
            return self::error('Не удалось найти домофон', 404);
        }

        if (!$device->ping()) {
            return self::error('Домофон не доступен', 400);
        }

        if ($request->apartment) {
            $device->call($request->apartment);
        } else {
            $device->callStop();
        }

        return self::success();
    }

    /**
     * Получить уровни с домофона
     */
    #[Post('/level/{id}')]
    public function level(IntercomDeviceLevelRequest $request): ResponseInterface
    {
        $device = intercom($request->id);

        if (!$device) {
            return self::error('Не удалось найти домофон', 404);
        }

        if (!$device->ping()) {
            return self::error('Домофон не доступен', 400);
        }

        if (!$device instanceof CmsInterface) {
            return self::error('Домофон не поддерживает КМС', 400);
        }

        if (!is_null($request->apartment)) {
            return self::success($request->info ? $device->getLineDialStatus($request->apartment, true) : ['resist' => $device->getLineDialStatus($request->apartment, false)]);
        }

        if (!is_null($request->from) && !is_null($request->to)) {
            return self::success($device->getAllLineDialStatus($request->from, $request->to, $request->info));
        }

        return self::error('Не достаточно данных', 400);
    }

    /**
     * Открыть реле домофона
     */
    #[Post('/open/{id}')]
    public function open(IntercomDeviceOpenRequest $request): ResponseInterface
    {
        $device = intercom($request->id);

        if (!$device) {
            return self::error('Не удалось найти домофон', 404);
        }

        if (!$device->ping()) {
            return self::error('Домофон не доступен', 400);
        }

        $device->open($request->output);

        return self::success();
    }

    /**
     * Обновить пароль на домофоне
     */
    #[Post('/password/{id}')]
    public function password(IntercomDevicePasswordRequest $request, SipFeature $sipFeature, AuditFeature $auditFeature): ResponseInterface
    {
        $device = intercom($request->id);

        if (!$device) {
            return self::error('Не удалось найти домофон', 404);
        }

        if (!$device->ping()) {
            return self::error('Домофон не доступен', 400);
        }

        $deviceCamera = DeviceCamera::fetch(criteria()->equal('url', $device->intercom->url));

        $password = $request->password ? $request->password : generate_password();

        file_logger('password')->debug('Обновление пароля устройства', ['id' => $device->intercom->house_domophone_id, 'oldPassword' => $device->intercom->credentials, 'password' => $password]);

        try {
            $sipServer = $sipFeature->server('ip', $device->intercom->server)[0];

            $username = sprintf('1%05d', $device->intercom->house_domophone_id);

            if ($device instanceof SipInterface) {
                $device->setSip(new Sip($username, $password, $sipServer->internal_ip, $sipServer->internal_port));
            }
        } catch (Throwable $throwable) {
            file_logger('password')->error($throwable);

            return self::error('Неудалось обновить sip аккаунт домофона', 500);
        }

        $device->setLoginPassword($password);

        $oldIntercomPassword = $device->intercom->credentials;
        $device->intercom->credentials = $password;

        if (!$device->intercom->safeUpdate()) {
            file_logger('password')->info('Неудалось сохранить новый пароль в базе данных у домофона', ['old' => $oldIntercomPassword, 'new' => $password]);

            return self::error('Неудалось сохранить новый пароль в базе данных у домофона (новый пароль' . $password . ', старый пароль ' . $oldIntercomPassword . ')', 400);
        }

        if ($deviceCamera instanceof DeviceCamera) {
            $oldCameraPassword = $device->intercom->credentials;

            if ($deviceCamera->stream && trim($deviceCamera->stream) !== '') {
                $deviceCamera->stream = (string) (new Uri($deviceCamera->stream))->withUserInfo($device->login, $password);
            }

            $deviceCamera->credentials = $password;

            if (!$deviceCamera->safeUpdate()) {
                file_logger('password')->info('Неудалось сохранить новый пароль в базе данных у камеры', ['old' => $oldCameraPassword, 'new' => $password]);

                return self::error('Неудалось сохранить новый пароль в базе данных у камеры (новый пароль' . $password . ', старый пароль ' . $oldCameraPassword . ')', 400);
            }

            if ($deviceCamera->dvr_server_id) {
                try {
                    dvr($deviceCamera->dvr_server_id)->updateCamera($deviceCamera);
                } catch (Throwable $throwable) {
                    file_logger('password')->info('Неудалось обновить пароль на DVR' . PHP_EOL . $throwable, ['old' => $oldCameraPassword, 'new' => $password]);
                }
            }
        }

        if ($auditFeature->canAudit()) {
            $auditFeature->audit(strval($device->intercom->house_domophone_id), DeviceIntercom::class, 'password', 'Обновление пароля');
        }

        return self::success();
    }

    /**
     * Перезапустить домофон
     */
    #[Post('/reboot/{id}')]
    public function reboot(int $id, AuditFeature $feature): ResponseInterface
    {
        $device = intercom($id);

        if (!$device) {
            return self::error('Не удалось найти домофон', 404);
        }

        if (!$device->ping()) {
            return self::error('Домофон не доступен', 400);
        }

        $device->reboot();

        if ($feature->canAudit()) {
            $feature->audit(strval($id), DeviceIntercom::class, 'reboot', 'Перезапуск домофона');
        }

        return self::success();
    }

    /**
     * Сброс домофона
     */
    #[Post('/reset/{id}')]
    public function reset(IntercomDeviceResetRequest $request, AuditFeature $feature): ResponseInterface
    {
        $device = intercom($request->id);

        if (!$device) {
            return self::error('Не удалось найти домофон', 404);
        }

        if (!$device->ping()) {
            return self::error('Домофон не доступен', 400);
        }

        switch ($request->type) {
            case 'key':
                if ($device instanceof KeyInterface) {
                    $device->clearKey();
                }

                break;
            case 'apartment':
                if ($device instanceof ApartmentInterface) {
                    $device->clearApartments();
                }

                break;
            case 'reset':
            default:
                $device->reset();

                break;
        }

        if ($feature->canAudit()) {
            $feature->audit(strval($request->id), DeviceIntercom::class, 'reset', 'Сброс домофона');
        }

        return self::success();
    }

    /**
     * Синхронизация домофона
     * 
     * @param int $id Идентификатор домофона
     */
    #[Post('/sync/{id}')]
    public function sync(int $id, AuditFeature $feature): ResponseInterface
    {
        $device = intercom($id);

        if (!$device) {
            return self::error('Не удалось найти домофон', 404);
        }

        if (!$device->ping()) {
            return self::error('Домофон не доступен', 400);
        }

        task(new IntercomConfigureTask($id))->high()->dispatch();

        if ($feature->canAudit()) {
            $feature->audit(strval($id), DeviceIntercom::class, 'sync', 'Синхронизация домофона');
        }

        return self::success();
    }
}
