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
use Selpol\Service\RedisService;

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

        if ($result !== null) {
            return json_response(401, body: ['code' => 401, 'message' => $result])->withStatus(401);
        }

        return $handler->handle($request);
    }

    private function setJwtFromRequest(ServerRequest $request): ?string
    {
        $authorization = $request->getHeaderLine('Authorization');

        if (!str_starts_with($authorization, 'Bearer ')) {
            return 'Запрос не авторизирован';
        }

        $bearer = substr($authorization, 7);

        if (substr_count($bearer, '.') !== 2) {
            if ($this->user) {
                $ip = connection_ip($request);

                if ($ip === null || $ip === '' || $ip === '0') {
                    return 'Неизвестный источник запроса';
                }

                $user = container(AuthenticationFeature::class)->auth($bearer, $request->getHeaderLine('User-Agent'), $ip);

                if ($user && $user->aud_jti) {
                    container(AuthService::class)->setToken(new CoreAuthToken($bearer, $user->aud_jti));

                    return null;
                }
            }

            return 'Не верный формат токена';
        }

        $jwt = container(OauthFeature::class)->validateJwt($bearer);

        if ($jwt === null) {
            return 'Запрос не авторизирован';
        }

        if (container(RedisService::class)->jti()->exist($jwt['jti'])) {
            return 'Запрос заблокирован';
        }

        container(AuthService::class)->setToken(new JwtAuthToken($jwt));

        return null;
    }
}