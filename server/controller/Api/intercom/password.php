<?php

namespace Selpol\Controller\Api\intercom;

use Selpol\Controller\Api\Api;
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

        if ($deviceIntercom) {
            $deviceCamera = DeviceCamera::fetch(criteria()->equal('url', $deviceIntercom->url));

            $intercom = container(DeviceService::class)->intercom($deviceIntercom->model, $deviceIntercom->url, $deviceIntercom->credentials);

            if ($intercom) {
                if (!$intercom->ping())
                    return self::error('Устройство не доступно', 404);

                $password = array_key_exists('password', $params) ? rule()->string()->clamp(8, 8)->onItem('password', $params) : generate_password();

                file_logger('intercom')->debug('Обновление пароля устройства', ['id' => $deviceIntercom->house_domophone_id, 'oldPassword' => $deviceIntercom->credentials, 'password' => $password]);

                try {
                    $sipServer = container(SipFeature::class)->server('ip', $deviceIntercom->server)[0];

                    $username = sprintf('1%05d', $deviceIntercom->house_domophone_id);

                    $intercom->setSip($username, $password, $sipServer->internal_ip, 5060);
                } catch (Throwable $throwable) {
                    file_logger('intercom')->error($throwable);

                    return self::error('Неудалось обновить sip аккаунт домофона');
                }

                $intercom->setLoginPassword($password);

                $oldIntercomPassword = $deviceIntercom->credentials;
                $deviceIntercom->credentials = $password;

                if (!$deviceIntercom->update()) {
                    file_logger('intercom')->info('Неудалось сохранить новый пароль в базе данных у домофона', ['old' => $oldIntercomPassword, 'new' => $password]);

                    return self::error('Неудалось сохранить новый пароль в базе данных у домофона (новый пароль' . $password . ', старый пароль ' . $oldIntercomPassword . ')', 400);
                }

                if ($deviceCamera) {
                    $oldCameraPassword = $deviceIntercom->credentials;

                    if ($deviceCamera->stream && trim($deviceCamera->stream) !== '')
                        $deviceCamera->stream = (string)(new Uri($deviceCamera->stream))->withUserInfo($intercom->login, $password);

                    $deviceCamera->credentials = $password;

                    if (!$deviceCamera->update()) {
                        file_logger('intercom')->info('Неудалось сохранить новый пароль в базе данных у камеры', ['old' => $oldCameraPassword, 'new' => $password]);

                        return self::error('Неудалось сохранить новый пароль в базе данных у камеры (новый пароль' . $password . ', старый пароль ' . $oldCameraPassword . ')', 400);
                    }
                }

                if (container(AuditFeature::class)->canAudit())
                    container(AuditFeature::class)->audit(strval($deviceIntercom->house_domophone_id), DeviceIntercom::class, 'password', 'Обновление пароля');

                return self::success();
            }

            return self::error('Неудалось определить модель устройства', 404);
        }

        return self::error('Домофон не найден', 404);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Домофон] Сгенерировать пароль'];
    }
}