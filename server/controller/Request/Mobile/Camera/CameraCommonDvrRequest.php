<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile\Camera;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id
 */
readonly class CameraCommonDvrRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id()
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'id' => 'Камера'
        ];
    }
}