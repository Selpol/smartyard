<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Internal;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $date Дата события
 * @property-read string $ip IP-адрес устройства
 * @property-read int $event Тип события
 * @property-read int $door Номер входа на устройстве
 * @property-read string $detail Дополнительные детали события
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

            'date' => rule()->required()->nonNullable()
        ];
    }
}