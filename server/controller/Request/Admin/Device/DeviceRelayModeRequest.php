<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Device;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор устройства
 * @property-read bool $value Состояние реле
 */
readonly class DeviceRelayModeRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'value' => rule()->required()->bool()->nonNullable(),
        ];
    }
}