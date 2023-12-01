<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $eventId
 *
 * @property-read int|null $flat_id
 * @property-read int|null $face_id
 */
readonly class FrsDeleteRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'eventId' => rule()->uuid(),

            'flat_id' => rule()->int()->clamp(0),
            'face_id' => rule()->int()->clamp(0)
        ];
    }
}