<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read null|string $type Тип мониторинга
 *
 * @property-read int[] $ids Список идентификаторов устройств
 */
readonly class MonitorIntercomRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'type' => rule()->in(['ping', 'sip']),

            'ids' => rule()->required()->array()->nonNullable(),
            'ids.*' => rule()->int()->nonNullable()
        ];
    }
}