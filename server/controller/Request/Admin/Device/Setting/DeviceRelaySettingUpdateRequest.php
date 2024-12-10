<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Device\Setting;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор устройства
 *
 * @property-read null|string $authentication Авторизация в формате Base64
 *
 * @property-read null|int $open_duration Время октрытия реле
 *
 * @property-read null|string $ping_address Ip-адрес для пинга
 * @property-read null|int $ping_timeout Таймаут пинга
 */
readonly class DeviceRelaySettingUpdateRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'authentication' => rule()->string(),

            'open_duration' => rule()->int()->clamp(0, 600),

            'ping_address' => rule()->ipV4(),
            'ping_timeout' => rule()->int()->clamp(0, 1000)
        ];
    }
}
