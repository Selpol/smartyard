<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $cameraId
 *
 * @property-read int $date
 */
readonly class CameraEventsRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'cameraId' => rule()->id(),
            'date' => [filter()->default(1), rule()->int()->clamp(0, 14)->nonNullable()]
        ];
    }
}