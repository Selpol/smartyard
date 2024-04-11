<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile\Camera;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int|null $house_id
 * @property-read int|null $flat_id
 * @property-read int|null $entrance_id
 */
readonly class CameraGetRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'house_id' => rule()->int(),
            'flat_id' => rule()->int(),
            'entrance_id' => rule()->int()
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'house_id' => 'Дом',
            'flat_id' => 'Квартира',
            'entrance_id' => 'Вход',
        ];
    }
}