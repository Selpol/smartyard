<?php

namespace Selpol\Middleware;

use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Feature\Oauth\OauthFeature;
use Selpol\Http\ServerRequest;
use Selpol\Service\Auth\Token\JwtAuthToken;
use Selpol\Service\AuthService;
use Selpol\Service\HttpService;

class JwtMiddleware implements MiddlewareInterface
{
    /**
     * @throws NotFoundExceptionInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->setJwtFromRequest($request);

        if ($result !== null) {
            /** @var HttpService $http */
            $http = $request->getAttribute('http');

            return $http->createResponse(401)->withJson(['code' => 401, 'message' => $result]);
        }

        return $handler->handle($request);
    }

    /**
     * @throws NotFoundExceptionInterface
     */
    private function setJwtFromRequest(ServerRequest $request): ?string
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

        container(AuthService::class)->setToken(new JwtAuthToken($jwt));

        return null;
    }
}