<?php

namespace Selpol\Controller\Api\authentication;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Service\AuthService;

readonly class permission extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        return self::success(container(AuthService::class)->getPermissions());
    }

    public static function index(): array
    {
        return ['GET' => '[Авторизация] Получить список прав пользователя'];
    }
}