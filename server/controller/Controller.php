<?php declare(strict_types=1);

namespace Selpol\Controller;

use Selpol\Framework\Http\Response;
use Selpol\Router\RouterMatch;
use Selpol\Service\Auth\AuthTokenInterface;
use Selpol\Service\Auth\AuthUserInterface;
use Selpol\Service\AuthService;

readonly class Controller
{
    protected RouterMatch $route;

    public function __construct(RouterMatch $route)
    {
        $this->route = $route;
    }

    protected function getToken(): AuthTokenInterface
    {
        return container(AuthService::class)->getTokenOrThrow();
    }

    protected function getUser(): AuthUserInterface
    {
        return container(AuthService::class)->getUserOrThrow();
    }

    protected function rbtResponse(int $code = 200, mixed $data = null, ?string $name = null, ?string $message = null): Response
    {
        if ($code !== 204) {
            $body = ['code' => $code];

            if ($message === null) {
                if ($name)
                    $message = $name;
                else if (array_key_exists($code, Response::$codes))
                    $message = Response::$codes[$code]['message'];
            }

            if ($name === null) {
                if (array_key_exists($code, Response::$codes))
                    $body['name'] = Response::$codes[$code]['name'];
            } else $body['name'] = $name;

            if ($message !== null) $body['message'] = $message;
            if ($data !== null) $body['data'] = $data;

            return json_response($code, body: $body);
        }

        return http()->createResponse($code);
    }
}