<?php declare(strict_types=1);

namespace Selpol\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Feature\Authentication\AuthenticationFeature;
use Selpol\Framework\Http\Response;
use Selpol\Framework\Router\Route\RouteMiddleware;
use Selpol\Service\Auth\Token\RedisAuthToken;
use Selpol\Service\Auth\User\RedisAuthUser;
use Selpol\Service\AuthService;

readonly class TokenMiddleware extends RouteMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authorization = $request->getHeader('Authorization');
        $userAgent = $request->getHeader('User-Agent');

        if (count($authorization) == 0)
            return json_response(401, body: ['code' => 401, 'name' => Response::$codes[401]['name'], 'message' => 'Запрос не авторизирован']);
        else $authorization = $authorization[0];

        if (count($userAgent) == 0)
            return json_response(401, body: ['code' => 401, 'name' => Response::$codes[401]['name'], 'message' => 'Запрос не авторизирован']);
        else $userAgent = $userAgent[0];

        $auth = container(AuthenticationFeature::class)->auth($authorization, $userAgent, connection_ip($request));

        if (!$auth)
            return json_response(401, body: ['code' => 401, 'name' => Response::$codes[401]['name'], 'message' => 'Пользователь не авторизирован']);

        container(AuthService::class)->setToken(new RedisAuthToken($auth['token']));
        container(AuthService::class)->setUser(new RedisAuthUser($auth));

        return $handler->handle($request);
    }
}