<?php declare(strict_types=1);

namespace Selpol\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Feature\House\HouseFeature;
use Selpol\Service\Auth\User\SubscriberAuthUser;
use Selpol\Service\AuthService;

readonly class MobileMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $auth = container(AuthService::class);

        $token = $auth->getTokenOrThrow();

        $subscribers = container(HouseFeature::class)->getSubscribers('aud_jti', $token->getOriginalValue()['scopes'][1]);

        if (!$subscribers || count($subscribers) === 0)
            return json_response(401, body: ['code' => 401, 'message' => 'Абонент не найден'])->withStatus(401);

        $auth->setUser(new SubscriberAuthUser($subscribers[0]));

        return $handler->handle($request);
    }
}