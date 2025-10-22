<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile\Camera;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id
 * 
 * @property-read int|null $time
 */
readonly class CameraCommonDvrRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'time' => rule()->int()
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'id' => 'Камера'
        ];
    }
}