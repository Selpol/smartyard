<?php

namespace Selpol\Controller\Api\intercom;

use Selpol\Controller\Api\Api;
use Selpol\Entity\Repository\Device\DeviceCameraRepository;
use Selpol\Entity\Repository\Device\DeviceIntercomRepository;
use Selpol\Feature\Sip\SipFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Http\Uri;
use Selpol\Service\DeviceService;
use Throwable;

readonly class password extends Api
{
    public static function GET(array $params): array|Response
    {
        $deviceIntercom = container(DeviceIntercomRepository::class)->findById(rule()->id()->onItem('_id', $params));

        if ($deviceIntercom) {
            $deviceCamera = container(DeviceCameraRepository::class)->fetch(criteria()->equal('url', $deviceIntercom->url));

            $intercom = container(DeviceService::class)->intercom($deviceIntercom->model, $deviceCamera->url, $deviceIntercom->credentials);

            if ($intercom) {
                if (!$intercom->ping())
                    return self::ERROR('Устройство не доступно');

                $password = generate_password();

                try {
                    $sipServer = container(SipFeature::class)->server('ip', $deviceIntercom->server)[0];

                    $username = sprintf('1%05d', $deviceIntercom->house_domophone_id);

                    $intercom->setSip($username, $password, $sipServer->internal_ip, 5060);
                } catch (Throwable $throwable) {
                    file_logger('intercom')->error($throwable);

                    return self::ERROR('Неудалось обновить sip аккаунт домофона');
                }

                $intercom->setLoginPassword($password);

                $oldIntercomPassword = $deviceIntercom->credentials;
                $deviceIntercom->credentials = $password;

                if (!container(DeviceIntercomRepository::class)->update($deviceIntercom)) {
                    file_logger('intercom')->info('Неудалось сохранить новый пароль в базе данных у домофона', ['old' => $oldIntercomPassword, 'new' => $password]);

                    return self::ERROR('Неудалось сохранить новый пароль в базе данных у домофона (новый пароль' . $password . ', старый пароль ' . $oldIntercomPassword . ')');
                }

                if ($deviceCamera) {
                    $oldCameraPassword = $deviceIntercom->credentials;

                    $deviceCamera->stream = (string)(new Uri($deviceCamera->stream))->withUserInfo($intercom->login, $password);
                    $deviceCamera->credentials = $password;

                    if (!container(DeviceCameraRepository::class)->update($deviceCamera)) {
                        file_logger('intercom')->info('Неудалось сохранить новый пароль в базе данных у камеры', ['old' => $oldCameraPassword, 'new' => $password]);

                        return self::ERROR('Неудалось сохранить новый пароль в базе данных у камеры (новый пароль' . $password . ', старый пароль ' . $oldCameraPassword . ')');
                    }
                }

                return self::ANSWER();
            }

            return self::ERROR('Неудалось определить модель устройства');
        }

        return self::ERROR('Домофон не найден');
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Домофон] Сгенерировать пароль'];
    }
}