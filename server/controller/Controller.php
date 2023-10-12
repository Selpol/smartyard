<?php

namespace Selpol\Controller;

use Selpol\Http\Response;
use Selpol\Http\ServerRequest;
use Selpol\Router\RouterMatch;
use Selpol\Service\Auth\AuthTokenInterface;
use Selpol\Service\Auth\AuthUserInterface;
use Selpol\Service\AuthService;
use Selpol\Service\HttpService;

class Controller
{
    protected ServerRequest $request;

    public function __construct(ServerRequest $request)
    {
        $this->request = $request;
    }

    protected function getHttp(): HttpService
    {
        return $this->request->getAttribute('http');
    }

    protected function getRoute(): RouterMatch
    {
        return $this->request->getAttribute('route');
    }

    protected function getToken(): AuthTokenInterface
    {
        return container(AuthService::class)->getTokenOrThrow();
    }

    protected function getUser(): AuthUserInterface
    {
        return container(AuthService::class)->getUserOrThrow();
    }

    protected function response(int $code = 200): Response
    {
        return $this->getHttp()->createResponse($code);
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

            return $this->response($code)->withJson($body);
        }

        return $this->response($code);
    }
}