<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Intercom;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор устройства
 *
 * @property-read string $type Тип действия
 */
readonly class IntercomDeviceResetRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'type' => [filter()->default('reset'), rule()->string()->in(['reset', 'key', 'apartment'])]
        ];
    }
}