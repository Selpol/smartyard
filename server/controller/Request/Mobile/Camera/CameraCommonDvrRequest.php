<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile\Camera;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id
 *
 * @property-read int|null $house_id
 * @property-read int|null $flat_id
 *
 * @property-read int|null $time
 */
readonly class CameraCommonDvrRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'time' => rule()->int()->clamp(0)
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'id' => 'Камера',

            'time' => 'Время'
        ];
    }
}