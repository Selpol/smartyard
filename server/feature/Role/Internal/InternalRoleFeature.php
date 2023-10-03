<?php declare(strict_types=1);

namespace Selpol\Feature\Role\Internal;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Entity\Model\Permission;
use Selpol\Entity\Model\Role;
use Selpol\Feature\Role\RoleFeature;
use Selpol\Validator\Exception\ValidatorException;

class InternalRoleFeature extends RoleFeature
{
    /**
     * @throws NotFoundExceptionInterface
     */
    public function roles(): array
    {
        return Role::fetchAll('SELECT * FROM role');
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function permissions(): array
    {
        return Permission::fetchAll('SELECT * FROM permission');
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function findPermissionsForRole(int $roleId): array
    {
        return Permission::fetchAll('SELECT * FROM permission WHERE id IN(SELECT permission_id FROM role_permission WHERE role_id = :role_id)', ['role_id' => $roleId]);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function findRolesForUser(int $userId): array
    {
        return Permission::fetchAll('SELECT * FROM role WHERE id IN(SELECT role_id FROM user_role WHERE user_id = :user_id)', ['user_id' => $userId]);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function findPermissionsForUser(int $userId): array
    {
        return Permission::fetchAll('SELECT * FROM permission WHERE id IN(SELECT permission_id FROM user_permission WHERE user_id = :user_id)', ['user_id' => $userId]);
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

        $role->insert();
        $role->refresh();

        return $role;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     * @throws ValidatorException
     */
    public function updateRole(int $roleId, string $title, string $description): Role
    {
        $role = Role::fetchById($roleId);

        $role->title = $title;
        $role->description = $description;

        $role->update();
        $role->refresh();

        return $role;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ValidatorException
     */
    public function deleteRole(int $roleId): bool
    {
        return Role::fetchById($roleId)->delete();
    }

    /**
     * @throws ValidatorException
     * @throws NotFoundExceptionInterface
     */
    public function updatePermission(int $permissionId, string $description): Permission
    {
        $permission = Permission::fetchById($permissionId);

        $permission->description = $description;

        $permission->update();
        $permission->refresh();

        return $permission;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function addPermissionToRole(int $roleId, int $permissionId): bool
    {
        $success = $this->getDatabase()->insert('INSERT INTO role_permission(role_id, permission_id) VALUES(:role_id, :permission_id)', ['role_id' => $roleId, 'permission_id' => $permissionId]);

        return $success == true || $success == 1;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function deletePermissionFromRole(int $roleId, int $permissionId): bool
    {
        $success = $this->getDatabase()->modify('DELETE FROM role_permission WHERE role_id = :role_id AND permission_id = :permission_id', ['role_id' => $roleId, 'permission_id' => $permissionId]);

        return $success == true || $success == 1;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function addRoleToUser(int $userId, int $roleId): bool
    {
        $success = $this->getDatabase()->insert('INSERT INTO user_role(user_id, role_id) VALUES(:user_id, :role_id)', ['user_id' => $userId, 'role_id' => $roleId]);

        return $success == true || $success == 1;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function deleteRoleFromUser(int $userId, int $roleId): bool
    {
        $success = $this->getDatabase()->modify('DELETE FROM user_role WHERE user_id = :user_id AND role_id = :role_id', ['user_id' => $userId, 'role_id' => $roleId]);

        return $success == true || $success == 1;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function addPermissionToUser(int $userId, int $permissionId): bool
    {
        $success = $this->getDatabase()->insert('INSERT INTO user_permission(user_id, permission_id) VALUES(:user_id, :permission_id)', ['user_id' => $userId, 'permission_id' => $permissionId]);

        return $success == true || $success == 1;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function deletePermissionFromUser(int $userId, int $permissionId): bool
    {
        $success = $this->getDatabase()->modify('DELETE FROM user_permission WHERE user_id = :user_id AND permission_id = :permission_id', ['user_id' => $userId, 'permission_id' => $permissionId]);

        return $success == true || $success == 1;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function getAllPermissionsForUser(int $userId): array
    {
        if ($userId === 0)
            return ['*'];

        $result = $this->findPermissionsForUser($userId);
        $roles = $this->findRolesForUser($userId);

        foreach ($roles as $role)
            $result = array_merge($result, $this->findPermissionsForRole($role->id));

        return array_map(static fn(Permission $permission) => $permission->title, $result);
    }

    public function getDefaultPermissions(): array
    {
        return config('feature.role.default_permissions', []);
    }
}