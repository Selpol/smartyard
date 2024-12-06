<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Device\Setting;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор устройства
 * @property-read int $sleep Задержка между состоянием
 */
readonly class DeviceRelaySettingFlapRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'sleep' => [filter()->default(3), rule()->required()->int()->clamp(0, 10)->nonNullable()],
        ];
    }
}
