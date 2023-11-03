<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Internal;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $stream_id
 *
 * @property-read int $eventId
 * @property-read int $faceId
 */
readonly class FrsCallbackRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'stream_id' => rule()->id(),

            'eventId' => rule()->required()->int()->clamp(1),
            'faceId' => rule()->required()->int()->clamp(1)
        ];
    }
}