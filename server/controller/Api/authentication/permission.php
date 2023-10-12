<?php

namespace Selpol\Controller\Api\authentication;

use Selpol\Controller\Api\api;
use Selpol\Service\AuthService;

class permission extends api
{
    public static function GET(array $params): array
    {
        return self::SUCCESS('permissions', container(AuthService::class)->getPermissions());
    }

    public static function index(): array
    {
        return ['GET' => '[Авторизация] Получить список прав пользователя'];
    }
}