<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Frs;

use Selpol\Entity\Repository\Frs\FrsFaceRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $face_id
 *
 * @property string $face_uuid
 * @property string $event_uuid
 */
class FrsFace extends Entity
{
    /**
     * @use RepositoryTrait<FrsFaceRepository>
     */
    use RepositoryTrait;

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