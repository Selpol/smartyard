<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $houseId
 */
readonly class CameraIndexRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'houseId' => rule()->id()
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'houseId' => 'Идентификатор'
        ];
    }
}