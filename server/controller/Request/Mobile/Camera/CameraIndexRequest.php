<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile\Camera;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $houseId
 */
readonly class CameraIndexRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'houseId' => rule()->int()
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'houseId' => 'Идентификатор'
        ];
    }
}