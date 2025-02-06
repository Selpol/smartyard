<?php declare(strict_types=1);

namespace Selpol\Feature\Role;

use Selpol\Feature\Feature;
use Selpol\Feature\Role\Internal\InternalRoleFeature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalRoleFeature::class)]
readonly abstract class RoleFeature extends Feature
{
    public abstract function getAllPermissionsForUser(int $userId): array;

    public abstract function getDefaultPermissions(): array;
}