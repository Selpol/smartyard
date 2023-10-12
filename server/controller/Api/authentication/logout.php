<?php

namespace Selpol\Controller\Api\authentication;

use Selpol\Controller\Api\api;
use Selpol\Feature\Authentication\AuthenticationFeature;

class logout extends api
{
    public static function POST(array $params): array
    {
        container(AuthenticationFeature::class)->logout($params["_token"], @$params['mode'] == 'all');

        return ["204" => null];
    }

    public static function index(): bool|array
    {
        return ["POST" => "[Авторизация] Выйти"];
    }
}