<?php declare(strict_types=1);

namespace Selpol\Entity\Model;

use Selpol\Entity\Repository\PermissionRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $id
 *
 * @property string $title
 * @property string $description
 *
 * @property string $created_at
 * @property string $updated_at
 */
class Permission extends Entity
{
    /**
     * @use RepositoryTrait<PermissionRepository>
     */
    use RepositoryTrait;

    public static string $table = 'permission';

    public static string $columnIdStrategy = 'permission_id_seq';

    public static ?string $columnCreatedAt = 'created_at';

    public static ?string $columnUpdateAt = 'updated_at';

    public static function getColumns(): array
    {
        return [
            static::$columnId => rule()->id(),

            'title' => rule()->required()->string()->nonNullable(),
            'description' => rule()->required()->string()->nonNullable(),

            'created_at' => rule()->string(),
            'updated_at' => rule()->string()
        ];
    }
}