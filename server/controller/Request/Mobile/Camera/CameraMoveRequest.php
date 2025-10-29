<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile\Camera;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $id
 *
 * @property-read double $lat
 * @property-read double $lon
 */
readonly class CameraMoveRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'id' => rule()->id(),

            'lat' => rule()->float()->exist(),
            'lon' => rule()->float()->exist(),
        ];
    }
}