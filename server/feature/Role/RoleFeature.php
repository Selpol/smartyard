<?php declare(strict_types=1);

namespace Selpol\Feature\Role;

use Selpol\Entity\Model\Role;
use Selpol\Feature\Feature;
use Selpol\Feature\Role\Internal\InternalRoleFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalRoleFeature::class)]
readonly abstract class RoleFeature extends Feature
{
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