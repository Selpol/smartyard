<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Frs;

use Selpol\Entity\Entity;
use Selpol\Validator\Rule;

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

    protected static function getColumns(): array
    {
        return [
            static::$columnId => [Rule::id()],

            'face_uuid' => [Rule::required(), Rule::uuid(), Rule::nonNullable()],
            'event_uuid' => [Rule::required(), Rule::uuid(), Rule::nonNullable()]
        ];
    }
}