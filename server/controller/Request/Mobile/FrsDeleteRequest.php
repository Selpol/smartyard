<?php declare(strict_types=1);

namespace Selpol\Controller\Request\Mobile;

use Selpol\Framework\Router\Route\RouteRequest;

/**
 * @property-read int $eventId
 *
 * @property-read int|null $flat_id
 * @property-read int|null $face_id
 *
 * @property-read int|null $flatId
 * @property-read int|null $faceId
 *
 */
readonly class FrsDeleteRequest extends RouteRequest
{
    public static function getValidate(): array
    {
        return [
            'eventId' => rule()->uuid4(),

            'flat_id' => rule()->int()->clamp(0),
            'face_id' => rule()->int()->clamp(0),

            'flatId' => rule()->int()->clamp(0),
            'faceId' => rule()->int()->clamp(0)
        ];
    }

    public static function getValidateTitle(): array
    {
        return [
            'eventId' => 'Идентификатор',

            'flat_id' => 'Идентификатор',
            'face_id' => 'Идентификатор',

            'flatId' => 'Идентификатор',
            'faceId' => 'Идентификатор'
        ];
    }
}