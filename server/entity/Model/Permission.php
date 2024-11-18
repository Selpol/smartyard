<?php declare(strict_types=1);

namespace Selpol\Entity\Model;

use Selpol\Entity\Model\Core\CoreUser;
use Selpol\Entity\Repository\PermissionRepository;
use Selpol\Framework\Entity\Entity;
use Selpol\Framework\Entity\Relationship\ManyToManyRelationship;
use Selpol\Framework\Entity\Trait\RelationshipTrait;
use Selpol\Framework\Entity\Trait\RepositoryTrait;

/**
 * @property int $id
 *
 * @property string $title
 * @property string $description
 *
 * @property string $created_at
 * @property string $updated_at
 *
 * @property-read Role[] $roles
 * @property-read CoreUser[] $users
 */
class Permission extends Entity
{
    /**
     * @use RepositoryTrait<PermissionRepository>
     */
    use RepositoryTrait;
    use RelationshipTrait;

    public static string $table = 'permission';

    public static string $columnIdStrategy = 'permission_id_seq';

    public static ?string $columnCreatedAt = 'created_at';

    public static ?string $columnUpdateAt = 'updated_at';

    /**
     * @return ManyToManyRelationship<Role>
     */
    public function roles(): ManyToManyRelationship
    {
        return $this->manyToMany(Role::class, 'role_permission', localRelation: 'permission_id', foreignRelation: 'role_id');
    }

    /**
     * @return ManyToManyRelationship<CoreUser>
     */
    public function users(): ManyToManyRelationship
    {
        return $this->manyToMany(CoreUser::class, 'user_permission', localRelation: 'permission_id', foreignRelation: 'user_id');
    }

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