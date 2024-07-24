<?php declare(strict_types=1);

namespace Selpol\Middleware\Mobile;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Feature\Authentication\AuthenticationFeature;
use Selpol\Feature\Oauth\OauthFeature;
use Selpol\Framework\Http\ServerRequest;
use Selpol\Framework\Router\Route\RouteMiddleware;
use Selpol\Service\Auth\Token\CoreAuthToken;
use Selpol\Service\Auth\Token\JwtAuthToken;
use Selpol\Service\AuthService;

readonly class AuthMiddleware extends RouteMiddleware
{
    private bool $user;

    public function __construct(array $config)
    {
        $this->user = $config['user'];
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->setJwtFromRequest($request);

        if ($result !== null)
            return json_response(401, body: ['code' => 401, 'message' => $result])->withStatus(401);

        return $handler->handle($request);
    }

    private function setJwtFromRequest(ServerRequest $request): ?string
    {
        $token = $request->getHeaderLine('Authorization');

        if (!str_starts_with($token, 'Bearer '))
            return 'Запрос не авторизирован';

        $bearer = substr($token, 7);

        if (substr_count($bearer, '.') !== 2) {
            if ($this->user) {
                $auth = container(AuthenticationFeature::class)->auth($token);

                if ($auth && $auth['user']['aud_jti']) {
                    container(AuthService::class)->setToken(new CoreAuthToken($auth['token'], $auth['user']['aud_jti']));

                    return null;
                }
            }

            return 'Не верный формат токена';
        }

        $jwt = container(OauthFeature::class)->validateJwt($bearer);

        if ($jwt === null)
            return 'Запрос не авторизирован';

        container(AuthService::class)->setToken(new JwtAuthToken($jwt));

        return null;
    }
}