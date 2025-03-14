<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Admin\Entrance;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id Идентификатор входа
 * 
 * @property-read array $flats Квартиры
 */
readonly class EntranceFlatRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'flats' => rule()->array()->exist(),
            'flats.*.flatId' => rule()->id(),
            'flats.*.apartment' => rule()->int()->clamp(0)->exist(),
        ];
    }
}