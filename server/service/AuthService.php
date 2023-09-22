<?php

namespace Selpol\Service;

use Psr\Container\NotFoundExceptionInterface;
use Selpol\Feature\Oauth\OauthFeature;
use Selpol\Http\HttpException;
use Selpol\Http\ServerRequest;

class AuthService
{
    private ?array $jwt = null;
    private ?array $subscriber = null;

    public function getJwt(): ?array
    {
        return $this->jwt;
    }

    public function getJwrOrThrow(): array
    {
        if ($this->jwt === null)
            throw new HttpException(message: 'Запрос не авторизирован', code: 401);

        return $this->jwt;
    }

    public function setJwt(?array $jwt): void
    {
        $this->jwt = $jwt;
    }

    public function getSubscriber(): ?array
    {
        return $this->subscriber;
    }

    public function getSubscriberOrThrow(): array
    {
        if ($this->subscriber === null)
            throw new HttpException(message: 'Запрос не авторизирован', code: 401);

        return $this->subscriber;
    }

    public function setSubscriber(?array $subscriber): void
    {
        $this->subscriber = $subscriber;
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    public function setJwtFromRequest(ServerRequest $request): ?string
    {
        $token = $request->getHeader('Authorization');

        if (count($token) === 0 || !str_starts_with($token[0], 'Bearer '))
            return 'Запрос не авторизирован';

        $bearer = substr($token[0], 7);

        if (substr_count($bearer, '.') !== 2)
            return 'Не верный формат токена';

        $jwt = container(OauthFeature::class)->validateJwt($bearer);

        if ($jwt === null)
            return 'Запрос не авторизирован';

        $this->setJwt($jwt);

        return null;
    }
}