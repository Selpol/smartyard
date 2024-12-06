<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Device\Setting;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор устройства
 *
 * @property-read null|int $pin Пин управления
 * @property-read null|bool $invert Инвертный пин управления
 * @property-read null|string $authentication Авторизация в формате Base64
 * @property-read null|string $ping_address Ip-адрес для пинга
 * @property-read null|int $ping_timeout Таймаут пинга
 */
readonly class DeviceRelaySettingUpdateRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'pin' => rule()->int()->clamp(0, 27),
            'invert' => rule()->bool(),
            'authentication' => rule()->string(),
            'ping_address' => rule()->ipV4(),
            'ping_timeout' => rule()->int()->clamp(0, 1000)
        ];
    }
}
