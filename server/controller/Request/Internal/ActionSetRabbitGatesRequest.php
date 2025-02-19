<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Internal;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $ip IP-адрес устройства
 * @property-read int $prefix Префикс устройства
 * @property-read int $apartmentNumber Номер квартиры
 * @property-read int $apartmentId Идентификатор квартиры
 * @property-read int $date Дата события
 */
readonly class ActionSetRabbitGatesRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'ip' => rule()->required()->ipV4()->nonNullable(),

            'prefix' => rule()->required()->int()->nonNullable(),
            'apartmentNumber' => rule()->required()->int()->nonNullable(),
            'apartmentId' => rule()->required()->int()->nonNullable(),

            'date' => rule()->required()->nonNullable()
        ];
    }
}