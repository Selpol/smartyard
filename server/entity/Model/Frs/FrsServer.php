<?php declare(strict_types=1);

namespace Selpol\Entity\Model\Frs;

use Selpol\Entity\Model\Device\DeviceCamera;
use Selpol\Entity\Repository\Frs\FrsServerRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\OneToManyRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $id
 *
 * @property string $title
 *
 * @property string $url
 *
 * @property string $created_at
 * @property string $updated_at
 * 
 * @property-read DeviceCamera[] $cameras
 */
class FrsServer extends Entity
{
    /**
     * @use RepositoryTrait<FrsServerRepository>
     */
    use RepositoryTrait;
    use RelationshipTrait;

    public static string $table = 'frs_servers';

    public static string $columnIdStrategy = 'frs_servers_id_seq';

    public static ?string $columnCreateAt = 'created_at';

    public static ?string $columnUpdateAt = 'updated_at';

    /**
     * @return OneToManyRelationship<DeviceCamera>
     */
    public function cameras(): OneToManyRelationship
    {
        return $this->oneToMany(DeviceCamera::class, 'frs_server_id', 'id');
    }

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'title' => rule()->required()->string()->nonNullable(),

            'url' => rule()->required()->url()->nonNullable(),

            'created_at' => rule()->string(),
            'updated_at' => rule()->string()
        ];
    }
}