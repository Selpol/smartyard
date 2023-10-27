<?php

namespace Selpol\Controller\Api\accounts;

use Selpol\Controller\Api\Api;
use Selpol\Feature\User\UserFeature;

readonly class users extends Api
{
    public static function GET(array $params): array
    {
        $users = container(UserFeature::class)->getUsers();

        return Api::ANSWER($users, ($users !== false) ? "users" : "notFound");
    }

    public static function index(): bool|array
    {
        return ["GET" => "[Пользователи] Получить список"];
    }
}