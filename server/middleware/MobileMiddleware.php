<?php

namespace Selpol\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Service\AuthService;
use Selpol\Service\HttpService;

class MobileMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $auth = container(AuthService::class);

        $jwt = $auth->getJwrOrThrow();

        $subscribers = backend('households')->getSubscribers('aud_jti', $jwt['scopes'][1]);

        if (!$subscribers || count($subscribers) === 0) {
            /** @var HttpService $http */
            $http = $request->getAttribute('http');

            return $http->createResponse(401)->withJson(['code' => 401, 'message' => 'Абонент не найден']);
        }

        $auth->setSubscriber($subscribers[0]);

        return $handler->handle($request);
    }
}