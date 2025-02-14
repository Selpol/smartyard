<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Intercom;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор устройства
 *
 * @property-read int $output Номер реле
 */
readonly class IntercomDeviceOpenRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'output' => [filter()->default(0), rule()->int()->clamp(0, 10)]
        ];
    }
}