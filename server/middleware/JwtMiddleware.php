<?php declare(strict_types=1);

namespace Selpol\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Feature\Oauth\OauthFeature;
use Selpol\Framework\Http\ServerRequest;
use Selpol\Service\Auth\Token\JwtAuthToken;
use Selpol\Service\AuthService;

readonly class JwtMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->setJwtFromRequest($request);

        if ($result !== null)
            return json_response(401, body: ['code' => 401, 'message' => $result])->withStatus(401);

        return $handler->handle($request);
    }

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