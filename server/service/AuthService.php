<?php declare(strict_types=1);

namespace Selpol\Service;

use Selpol\Feature\Authentication\AuthenticationFeature;
use Selpol\Feature\Role\RoleFeature;
use Selpol\Framework\Container\Attribute\Singleton;
use Selpol\Service\Auth\AuthTokenInterface;
use Selpol\Service\Auth\AuthUserInterface;
use Selpol\Service\Exception\AuthException;

#[Singleton]
class AuthService
{
    private ?AuthTokenInterface $token = null;

    private ?AuthUserInterface $user = null;

    /**
     * @var array<int, string[]>
     */
    private array $scopes = [];

    public function checkPassword(string $password): bool
    {
        $user = $this->getUser();

        if (!$user) {
            return false;
        }

        $result = container(AuthenticationFeature::class)->checkAuth($user->getUsername(), $password);

        return $user->getIdentifier() == $result;
    }

    public function getToken(): ?AuthTokenInterface
    {
        return $this->token;
    }

    public function getTokenOrThrow(): AuthTokenInterface
    {
        if (!$this->token instanceof AuthTokenInterface) {
            throw new AuthException(localizedMessage: 'Запрос не авторизирован', code: 401);
        }

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
        if (!$this->user instanceof AuthUserInterface) {
            throw new AuthException(localizedMessage: 'Запрос не авторизирован', code: 401);
        }

        return $this->user;
    }

    public function setUser(?AuthUserInterface $user): void
    {
        $this->user = $user;
    }

    public function getPermissions(): array
    {
        if (!$this->user instanceof AuthUserInterface || !$this->user->canScope()) {
            return [];
        }

        $identifier = intval($this->user->getIdentifier());

        return container(RoleFeature::class)->getAllPermissionsForUser($identifier);
    }

    public function checkScope(string $value): bool
    {
        if (!$this->user instanceof AuthUserInterface || !$this->user->canScope()) {
            return false;
        }

        return $this->checkUserScope(intval($this->user->getIdentifier()), $value);
    }

    public function checkUserScope(int $id, string $value): bool
    {
        $role = container(RoleFeature::class);

        $defaultPermissions = $role->getDefaultPermissions();

        if (in_array('*', $defaultPermissions) || in_array($value, $defaultPermissions)) {
            return true;
        }

        if (!array_key_exists($id, $this->scopes)) {
            $this->scopes[$id] = $role->getAllPermissionsForUser($id);
        }

        $permissions = $this->scopes[$id];

        return in_array('*', $permissions) || in_array($value, $permissions);
    }
}