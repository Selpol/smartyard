<?php

namespace Selpol\Controller\Api\accounts;

use Selpol\Controller\Api\Api;
use Selpol\Entity\Repository\Core\CoreUserRepository;

class password extends Api
{
    public static function POST(array $params): array
    {
        $user = container(CoreUserRepository::class)->findById($params['_id']);

        $user->password = password_hash($params['password'], PASSWORD_DEFAULT);

        if (container(CoreUserRepository::class)->update($user))
            return self::ANSWER($user->uid, 'userId');

        return self::ERROR('Неудалось обновить пароль');
    }

    public static function index(): array
    {
        return ["POST" => "[Пользователь] Обновить пароль"];
    }
}