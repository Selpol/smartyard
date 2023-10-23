<?php declare(strict_types=1);

namespace Selpol\Feature\Role;

use Selpol\Entity\Model\Permission;
use Selpol\Entity\Model\Role;
use Selpol\Feature\Feature;
use Selpol\Feature\Role\Internal\InternalRoleFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalRoleFeature::class)]
readonly abstract class RoleFeature extends Feature
{
    /**
     * @return array<Role>
     */
    public abstract function roles(): array;

    /**
     * @return array<Permission>
     */
    public abstract function permissions(): array;

    /**
     * @param int $roleId
     * @return array<Permission>
     */
    public abstract function findPermissionsForRole(int $roleId): array;

    /**
     * @param int $userId
     * @return array<Role>
     */
    public abstract function findRolesForUser(int $userId): array;

    /**
     * @param int $userId
     * @return array<Permission>
     */
    public abstract function findPermissionsForUser(int $userId): array;

    public abstract function createRole(string $title, string $description): Role;

    public abstract function updateRole(int $roleId, string $title, string $description): Role;

    public abstract function deleteRole(int $roleId): bool;

    public abstract function addPermissionToRole(int $roleId, int $permissionId): bool;

    public abstract function deletePermissionFromRole(int $roleId, int $permissionId): bool;

    public abstract function addRoleToUser(int $userId, int $roleId): bool;

    public abstract function deleteRoleFromUser(int $userId, int $roleId): bool;

    public abstract function addPermissionToUser(int $userId, int $permissionId): bool;

    public abstract function deletePermissionFromUser(int $userId, int $permissionId): bool;

    public abstract function getAllPermissionsForUser(int $userId): array;

    public abstract function getDefaultPermissions(): array;
}