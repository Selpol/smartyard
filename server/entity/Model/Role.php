<?php declare(strict_types=1);

namespace Selpol\Entity\Model;

use Selpol\Entity\Model\Core\CoreUser;
use Selpol\Entity\Repository\RoleRepository;
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
 * @property-read Permission[] $permissions
 * @property-read CoreUser[] $users
 */
class Role extends Entity
{
    /**
     * @use RepositoryTrait<RoleRepository>
     */
    use RepositoryTrait;
    use RelationshipTrait;

    public static string $table = 'role';

    public static string $columnIdStrategy = 'role_id_seq';

    public static ?string $columnCreateAt = 'created_at';

    public static ?string $columnUpdateAt = 'updated_at';

    /**
     * @return ManyToManyRelationship<Permission>
     */
    public function permissions(): ManyToManyRelationship
    {
        return $this->manyToMany(Permission::class, 'role_permission', localRelation: 'role_id', foreignRelation: 'permission_id');
    }

    /**
     * @return ManyToManyRelationship<CoreUser>
     */
    public function users(): ManyToManyRelationship
    {
        return $this->manyToMany(CoreUser::class, 'user_role', localRelation: 'role_id', foreignRelation: 'user_id');
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