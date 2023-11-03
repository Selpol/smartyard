<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Internal;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read string $ip
 * @property-read int $prefix
 * @property-read int $apartmentNumber
 * @property-read int $apartmentId
 * @property-read int $date
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
            'date' => rule()->required()->int()->nonNullable()
        ];
    }
}