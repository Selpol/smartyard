<?php declare(strict_types=1);

namespace Selpol\Feature\Role\Internal;

use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Selpol\Cache\RedisCache;
use Selpol\Entity\Model\Permission;
use Selpol\Entity\Repository\PermissionRepository;
use Selpol\Entity\Repository\RoleRepository;
use Selpol\Feature\Role\RoleFeature;

readonly class InternalRoleFeature extends RoleFeature
{
    public function addPermissionToRole(int $roleId, int $permissionId): bool
    {
        $success = $this->getDatabase()->insert('INSERT INTO role_permission(role_id, permission_id) VALUES(:role_id, :permission_id)', ['role_id' => $roleId, 'permission_id' => $permissionId]);

        return $success == true || $success == 1;
    }

    public function deletePermissionFromRole(int $roleId, int $permissionId): bool
    {
        $success = $this->getDatabase()->modify('DELETE FROM role_permission WHERE role_id = :role_id AND permission_id = :permission_id', ['role_id' => $roleId, 'permission_id' => $permissionId]);

        return $success == true || $success == 1;
    }

    public function addRoleToUser(int $userId, int $roleId): bool
    {
        $success = $this->getDatabase()->insert('INSERT INTO user_role(user_id, role_id) VALUES(:user_id, :role_id)', ['user_id' => $userId, 'role_id' => $roleId]);

        return $success == true || $success == 1;
    }

    public function deleteRoleFromUser(int $userId, int $roleId): bool
    {
        $success = $this->getDatabase()->modify('DELETE FROM user_role WHERE user_id = :user_id AND role_id = :role_id', ['user_id' => $userId, 'role_id' => $roleId]);

        return $success == true || $success == 1;
    }

    public function addPermissionToUser(int $userId, int $permissionId): bool
    {
        $success = $this->getDatabase()->insert('INSERT INTO user_permission(user_id, permission_id) VALUES(:user_id, :permission_id)', ['user_id' => $userId, 'permission_id' => $permissionId]);

        return $success == true || $success == 1;
    }

    public function deletePermissionFromUser(int $userId, int $permissionId): bool
    {
        $success = $this->getDatabase()->modify('DELETE FROM user_permission WHERE user_id = :user_id AND permission_id = :permission_id', ['user_id' => $userId, 'permission_id' => $permissionId]);

        return $success == true || $success == 1;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws InvalidArgumentException
     */
    public function getAllPermissionsForUser(int $userId): array
    {
        if ($userId === 0)
            return ['*'];

        return container(RedisCache::class)->cache('user:' . $userId . ':permission', function () use ($userId) {
            $result = container(PermissionRepository::class)->findByUserId($userId);
            $roles = container(RoleRepository::class)->findByUserId($userId);

            foreach ($roles as $role)
                $result = array_merge($result, container(PermissionRepository::class)->findByRoleId($role->id));

            return array_values(array_unique(array_map(static fn(Permission $permission) => $permission->title, $result)));
        }, 60);
    }

    public function getDefaultPermissions(): array
    {
        return config_get('feature.role.default_permissions', []);
    }
}