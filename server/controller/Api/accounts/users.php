<?php

namespace Selpol\Controller\Api\accounts;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\User\UserFeature;
use Selpol\Service\AuthService;

readonly class users extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $users = container(UserFeature::class)->getUsers();

        if ($users) {
            if (!container(AuthService::class)->checkScope('mobile-mask'))
                return self::success(array_map(static function (array $item) {
                    if ($item['phone'])
                        $item['phone'] = mobile_mask($item['phone']);

                    return $item;
                }, $users));

            return self::success($users);
        }

        return self::error('Пользователи не найдены', 404);
    }

    public static function index(): bool|array
    {
        return ["GET" => "[Пользователи] Получить список"];
    }
}