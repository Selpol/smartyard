<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Internal;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $stream_id Идентификатор потока
 *
 * @property-read int $eventId Идентификатор события
 * @property-read int $faceId Идентификатор лица
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