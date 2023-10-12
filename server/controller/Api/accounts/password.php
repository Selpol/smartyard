<?php

namespace Selpol\Controller\Api\accounts;

use Selpol\Controller\Api\Api;
use Selpol\Feature\User\UserFeature;

class password extends Api
{

    public static function POST(array $params): array
    {
        $success = container(UserFeature::class)->setPassword(@$params["_id"], $params["password"]);

        return self::ANSWER($success, ($success !== false) ? false : "notFound");
    }

    public static function index(): array
    {
        return ["POST" => "[Пользователь] Обновить пароль"];
    }
}