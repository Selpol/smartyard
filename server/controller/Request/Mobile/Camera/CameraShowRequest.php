<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile\Camera;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $houseId
 * @property-read int $id
 */
readonly class CameraShowRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'houseId' => rule()->id(),
            'id' => rule()->id(),
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'houseId' => 'Идентификатор',
            'id' => 'Камера'
        ];
    }
}