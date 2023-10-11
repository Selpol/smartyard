<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Frs;

use Selpol\Entity\Entity;

/**
 * @property int $face_id
 *
 * @property string $face_uuid
 * @property string $event_uuid
 */
class FrsFace extends Entity
{
    public static string $table = 'frs_faces';

    public static string $columnId = 'face_id';

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'face_uuid' => rule()->required()->uuid()->nonNullable(),
            'event_uuid' => rule()->required()->uuid()->nonNullable()
        ];
    }
}