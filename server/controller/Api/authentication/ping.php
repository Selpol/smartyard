<?php

namespace Selpol\Controller\Api\authentication;

use Selpol\Controller\Api\api;

class ping extends api
{

    public static function POST(array $params): array
    {
        return ["204" => null];
    }

    public static function index(): bool|array
    {
        return ['POST' => '[Авторизация] Проверка доступности'];
    }
}