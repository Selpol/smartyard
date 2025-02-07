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