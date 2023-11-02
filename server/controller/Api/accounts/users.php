<?php

namespace Selpol\Controller\Api\accounts;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\User\UserFeature;

readonly class users extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $users = container(UserFeature::class)->getUsers();

        if ($users)
            return self::success($users);

        return self::error('Пользователи не найдены', 404);
    }

    public static function index(): bool|array
    {
        return ["GET" => "[Пользователи] Получить список"];
    }
}