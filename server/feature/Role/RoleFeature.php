<?php declare(strict_types=1);

namespace Selpol\Feature\Role;

use Selpol\Feature\Feature;

abstract class RoleFeature extends Feature
{
    public abstract function roles(): array;

    public abstract function permissions(): array;

    public abstract function findPermissionsForRole(int $roleId): array;

    public abstract function findRolesForUser(int $userId): array;

    public abstract function findPermissionsForUser(int $userId): array;

    public abstract function findAllPermissionsForUser(int $userId): array;

    public abstract function createRole(string $title, string $description): ?int;

    public abstract function updateRole(int $id, string $title, string $description): bool;

    public abstract function deleteRole(int $roleId): bool;

    public abstract function addPermissionToRole(int $roleId, int $permissionId): bool;

    public abstract function deletePermissionFromRole(int $roleId, int $permissionId): bool;

    public abstract function addRoleToUser(int $userId, int $roleId): bool;

    public abstract function deleteRoleFromUser(int $userId, int $roleId): bool;

    public abstract function addPermissionToUser(int $userId, int $permissionId): bool;

    public abstract function deletePermissionFromUser(int $userId, int $permissionId): bool;

    public abstract function getDefaultPermissions(): array;
}