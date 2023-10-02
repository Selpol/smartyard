<?php declare(strict_types=1);

namespace Selpol\Feature\Role\Internal;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Feature\Role\RoleFeature;

class InternalRoleFeature extends RoleFeature
{
    /**
     * @throws NotFoundExceptionInterface
     */
    public function roles(): array
    {
        return $this->getDatabase()->get('SELECT * FROM role');
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function permissions(): array
    {
        return $this->getDatabase()->get('SELECT * FROM permission');
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function findPermissionsForRole(int $roleId): array
    {
        return $this->getDatabase()->get('SELECT * FROM permission WHERE id IN(SELECT permission_id FROM role_permission WHERE role_id = :role_id)', ['role_id' => $roleId]);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function findRolesForUser(int $userId): array
    {
        return $this->getDatabase()->get('SELECT * FROM role WHERE id IN(SELECT role_id FROM user_role WHERE user_id = :user_id)', ['user_id' => $userId]);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function findPermissionsForUser(int $userId): array
    {
        return $this->getDatabase()->get('SELECT * FROM permission WHERE id IN(SELECT permission_id FROM user_permission WHERE user_id = :user_id)', ['user_id' => $userId]);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function findAllPermissionsForUser(int $userId): array
    {
        if ($userId === 0)
            return ['*'];

        $result = $this->findPermissionsForUser($userId);
        $roles = $this->findRolesForUser($userId);

        foreach ($roles as $role)
            $result = array_merge($result, $this->findPermissionsForRole($role['id']));

        return array_column($result, 'title');
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function createRole(string $title, string $description): ?int
    {
        $id = $this->getDatabase()->get("SELECT NEXTVAL('permission_id_seq')", options: ['singlify'])['nextval'];

        $success = $this->getDatabase()->insert('INSERT INTO role (id, title, description) VALUES (:id, :title, :description)', ['id' => $id, 'title' => $title, 'description' => $description]);

        if ($success)
            return $id;

        return null;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function updateRole(int $id, string $title, string $description): bool
    {
        $success = $this->getDatabase()->modify('UPDATE role SET title = :title, description = :description, updated_at = now() WHERE id = :id', ['id' => $id, 'title' => $title, 'description' => $description]);

        return $success == true || $success == 1;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function deleteRole(int $roleId): bool
    {
        $success = $this->getDatabase()->modify('DELETE FROM role WHERE id = :id', ['id' => $roleId]);

        return $success == true || $success == 1;
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

    public function getDefaultPermissions(): array
    {
        return config('feature.role.default_permissions', []);
    }
}