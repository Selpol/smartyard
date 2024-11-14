<?php declare(strict_types=1);

namespace Selpol\Middleware\Admin;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Controller\AdminRbtController;
use Selpol\Feature\Authentication\AuthenticationFeature;
use Selpol\Framework\Router\Route\RouteMiddleware;
use Selpol\Service\Auth\Token\CoreAuthToken;
use Selpol\Service\Auth\User\CoreAuthUser;
use Selpol\Service\AuthService;

readonly class AuthMiddleware extends RouteMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ip = connection_ip($request);

        if ($ip === null || $ip === '' || $ip === '0') {
            return AdminRbtController::error('Неизвестный источник запроса', 401);
        }

        $authorization = $request->getHeader('Authorization');
        $userAgent = $request->getHeaderLine('User-Agent');

        if (count($authorization) === 0) {
            return AdminRbtController::error('Не передан токен авторизации', 401);
        }

        $segments = explode(' ', $authorization[0]);

        if (count($segments) !== 2 || $segments[0] != 'Bearer') {
            return AdminRbtController::error('Не верный токен авторизации', 401);
        }

        $user = container(AuthenticationFeature::class)->auth($segments[1], $userAgent, $ip);

        if (!$user) {
            return AdminRbtController::error('Запрос не авторизирован', 401);
        }

        container(AuthService::class)->setToken(new CoreAuthToken($segments[1], $user->aud_jti));
        container(AuthService::class)->setUser(new CoreAuthUser($user));

        return $handler->handle($request);
    }
}