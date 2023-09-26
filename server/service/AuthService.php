<?php declare(strict_types=1);

namespace Selpol\Service;

use Selpol\Http\Exception\HttpException;
use Selpol\Service\Auth\AuthTokenInterface;
use Selpol\Service\Auth\AuthUserInterface;

class AuthService
{
    private ?AuthTokenInterface $token = null;
    private ?AuthUserInterface $user = null;

    private ?array $subscriber = null;

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
}