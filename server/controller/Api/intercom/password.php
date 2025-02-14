<?php

namespace Selpol\Controller\Api\intercom;

use Selpol\Controller\Api\Api;
use Selpol\Device\Ip\Intercom\Setting\Sip\Sip;
use Selpol\Device\Ip\Intercom\Setting\Sip\SipInterface;
use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Feature\Audit\AuditFeature;
use Selpol\Feature\Sip\SipFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Http\Uri;
use Selpol\Service\DeviceService;
use Throwable;

readonly class password extends Api
{
    public static function GET(array $params): array|Response
    {
        $deviceIntercom = DeviceIntercom::findById(rule()->id()->onItem('_id', $params));

        if ($deviceIntercom instanceof DeviceIntercom) {
            $deviceCamera = DeviceCamera::fetch(criteria()->equal('url', $deviceIntercom->url));

            $intercom = container(DeviceService::class)->intercomByEntity($deviceIntercom);

            if ($intercom) {
                if (!$intercom->ping()) {
                    return self::error('Устройство не доступно', 404);
                }

                $password = array_key_exists('password', $params) ? rule()->string()->clamp(8, 8)->onItem('password', $params) : generate_password();

                file_logger('password')->debug('Обновление пароля устройства', ['id' => $deviceIntercom->house_domophone_id, 'oldPassword' => $deviceIntercom->credentials, 'password' => $password]);

                try {
                    $sipServer = container(SipFeature::class)->server('ip', $deviceIntercom->server)[0];

                    $username = sprintf('1%05d', $deviceIntercom->house_domophone_id);

                    if ($intercom instanceof SipInterface) {
                        $intercom->setSip(new Sip($username, $password, $sipServer->internal_ip, $sipServer->internal_port));
                    }
                } catch (Throwable $throwable) {
                    file_logger('password')->error($throwable);

                    return self::error('Неудалось обновить sip аккаунт домофона', 500);
                }

                $intercom->setLoginPassword($password);

                $oldIntercomPassword = $deviceIntercom->credentials;
                $deviceIntercom->credentials = $password;

                if (!$deviceIntercom->safeUpdate()) {
                    file_logger('password')->info('Неудалось сохранить новый пароль в базе данных у домофона', ['old' => $oldIntercomPassword, 'new' => $password]);

                    return self::error('Неудалось сохранить новый пароль в базе данных у домофона (новый пароль' . $password . ', старый пароль ' . $oldIntercomPassword . ')', 400);
                }

                if ($deviceCamera instanceof DeviceCamera) {
                    $oldCameraPassword = $deviceIntercom->credentials;

                    if ($deviceCamera->stream && trim($deviceCamera->stream) !== '') {
                        $deviceCamera->stream = (string)(new Uri($deviceCamera->stream))->withUserInfo($intercom->login, $password);
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

                if (container(AuditFeature::class)->canAudit()) {
                    container(AuditFeature::class)->audit(strval($deviceIntercom->house_domophone_id), DeviceIntercom::class, 'password', 'Обновление пароля');
                }

                return self::success();
            }

            return self::error('Неудалось определить модель устройства', 404);
        }

        return self::error('Домофон не найден', 404);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Deprecated] [Домофон] Сгенерировать пароль'];
    }
}