<?php declare(strict_types=1);

namespace Selpol\Feature\Role\Internal;

use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Selpol\Cache\RedisCache;
use Selpol\Entity\Model\Permission;
use Selpol\Entity\Model\Role;
use Selpol\Entity\Repository\PermissionRepository;
use Selpol\Entity\Repository\RoleRepository;
use Selpol\Feature\Role\RoleFeature;
use Selpol\Validator\Exception\ValidatorException;

class InternalRoleFeature extends RoleFeature
{
    /**
     * @throws NotFoundExceptionInterface
     */
    public function roles(): array
    {
        return container(RoleRepository::class)->fetchAll();
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function permissions(): array
    {
        return container(PermissionRepository::class)->fetchAll();
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function findPermissionsForRole(int $roleId): array
    {
        return container(PermissionRepository::class)->fetchAll(criteria()->where('id IN(SELECT permission_id FROM role_permission WHERE role_id = :role_id)')->bind('role_id', $roleId));
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function findRolesForUser(int $userId): array
    {
        return container(RoleRepository::class)->fetchAll(criteria()->where('id IN (SELECT role_id FROM user_role WHERE user_id = :user_id)')->bind('user_id', $userId));
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function findPermissionsForUser(int $userId): array
    {
        return container(PermissionRepository::class)->fetchAll(criteria()->where('id IN(SELECT permission_id FROM user_permission WHERE user_id = :user_id)')->bind('user_id', $userId));
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    public function createRole(string $title, string $description): Role
    {
        $role = new Role();

        $role->title = $title;
        $role->description = $description;

        container(RoleRepository::class)->insertAndRefresh($role);

        return $role;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     * @throws ValidatorException
     */
    public function updateRole(int $roleId, string $title, string $description): Role
    {
        $role = container(RoleRepository::class)->findById($roleId);

        $role->title = $title;
        $role->description = $description;

        container(RoleRepository::class)->updateAndRefresh($role);

        return $role;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    public function deleteRole(int $roleId): bool
    {
        $role = container(RoleRepository::class)->findById($roleId);

        return container(RoleRepository::class)->delete($role);
    }

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
            $result = $this->findPermissionsForUser($userId);
            $roles = $this->findRolesForUser($userId);

            foreach ($roles as $role)
                $result = array_merge($result, $this->findPermissionsForRole($role->id));

            return array_unique(array_map(static fn(Permission $permission) => $permission->title, $result));
        }, 60);
    }

    public function getDefaultPermissions(): array
    {
        return config_get('feature.role.default_permissions', []);
    }
}