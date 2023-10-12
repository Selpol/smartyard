<?php

namespace Selpol\Controller\Api\authentication;

use Selpol\Controller\Api\Api;
use Selpol\Service\AuthService;

class permission extends Api
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