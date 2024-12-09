<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Device\Setting;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор устройства
 * @property-read null|bool $value Состояние реле
 */
readonly class DeviceRelaySettingModeRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'value' => rule()->bool(),
        ];
    }
}
