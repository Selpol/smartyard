<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Internal;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $date
 * @property-read string $ip
 * @property-read int $event
 * @property-read int $door
 * @property-read string $detail
 */
readonly class ActionOpenDoorRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'ip' => rule()->required()->ipV4()->nonNullable(),
            'event' => rule()->required()->int()->nonNullable(),
            'door' => rule()->required()->int()->nonNullable(),
            'detail' => rule()->required()->string()->nonNullable(),

            'date' => rule()->required()->timestamp()->nonNullable()
        ];
    }
}