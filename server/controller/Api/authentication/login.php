<?php

namespace Selpol\Controller\Api\authentication;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Authentication\AuthenticationFeature;

readonly class login extends Api
{
    public static function POST(array $params): ResponseInterface
    {
        $auth = container(AuthenticationFeature::class)->login($params["login"], $params["password"], $params["rememberMe"] && $params["ua"] && $params["did"], trim($params["ua"]), trim($params["did"]), connection_ip(container(ServerRequestInterface::class)));

        if ($auth['result'])
            return self::success($auth['token']);

        return self::error('Не удалось войти', 401);
    }

    public static function index(): bool|array
    {
        return ['POST' => '[Авторизация] Войти'];
    }
}