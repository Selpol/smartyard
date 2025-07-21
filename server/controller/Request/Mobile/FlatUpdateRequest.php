<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id
 * @property-read int $call
 */
readonly class FlatUpdateRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),
            'call' => rule()->int()->in([0, 1])->exist(),
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'id' => 'Квартира',
            'call' => 'Звонки'
        ];
    }
}