<?php

namespace Selpol\Controller\Api\authentication;

use Selpol\Controller\Api\Api;
use Selpol\Feature\Authentication\AuthenticationFeature;

readonly class login extends Api
{
    public static function POST(array $params): array
    {
        $auth = container(AuthenticationFeature::class)->login($params["login"], $params["password"], $params["rememberMe"] && $params["ua"] && $params["did"], trim($params["ua"]), trim($params["did"]));

        if ($auth["result"]) return ["200" => ["token" => $auth["token"],],];
        else return [$auth["code"] => ["error" => $auth["error"],]];

    }

    public static function index(): bool|array
    {
        return ['POST' => '[Авторизация] Войти'];
    }
}