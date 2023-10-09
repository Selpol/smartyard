<?php declare(strict_types=1);

namespace Selpol\Service;

use Psr\Container\NotFoundExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Selpol\Cache\RedisCache;
use Selpol\Feature\Role\RoleFeature;
use Selpol\Http\Exception\HttpException;
use Selpol\Service\Auth\AuthTokenInterface;
use Selpol\Service\Auth\AuthUserInterface;

class AuthService
{
    private ?AuthTokenInterface $token = null;
    private ?AuthUserInterface $user = null;

    public function getToken(): ?AuthTokenInterface
    {
        return $this->token;
    }

    public function getTokenOrThrow(): AuthTokenInterface
    {
        if ($this->token === null)
            throw new HttpException(message: 'Запрос не авторизирован', code: 401);

        return $this->token;
    }

    public function setToken(?AuthTokenInterface $token): void
    {
        $this->token = $token;
    }

    public function getUser(): ?AuthUserInterface
    {
        return $this->user;
    }

    public function getUserOrThrow(): AuthUserInterface
    {
        if ($this->user === null)
            throw new HttpException(message: 'Запрос не авторизирован', code: 401);

        return $this->user;
    }

    public function setUser(?AuthUserInterface $user): void
    {
        $this->user = $user;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function getPermissions(): array
    {
        if ($this->user === null || !$this->user->canScope())
            return [];

        $identifier = intval($this->user->getIdentifier());

        return container(RoleFeature::class)->getAllPermissionsForUser($identifier);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function checkScope(string $value): bool
    {
        if ($this->user === null || !$this->user->canScope())
            return false;

        $role = container(RoleFeature::class);

        $defaultPermissions = $role->getDefaultPermissions();

        if (in_array('*', $defaultPermissions) || in_array($value, $defaultPermissions))
            return true;

        $identifier = intval($this->user->getIdentifier());

        $permissions = $role->getAllPermissionsForUser($identifier);

        return in_array('*', $permissions) || in_array($value, $permissions);
    }
}