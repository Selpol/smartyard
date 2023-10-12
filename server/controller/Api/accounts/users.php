<?php

namespace Selpol\Controller\Api\accounts;

use Selpol\Controller\Api\api;
use Selpol\Feature\User\UserFeature;

class users extends api
{
    public static function GET(array $params): array
    {
        $users = container(UserFeature::class)->getUsers();

        return api::ANSWER($users, ($users !== false) ? "users" : "notFound");
    }

    public static function index(): bool|array
    {
        return ["GET" => "[Пользователи] Получить список"];
    }
}