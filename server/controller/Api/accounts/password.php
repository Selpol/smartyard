<?php

namespace Selpol\Controller\Api\accounts;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Core\CoreUser;

readonly class password extends Api
{
    public static function POST(array $params): ResponseInterface
    {
        $user = CoreUser::findById($params['_id'], setting: setting()->nonNullable());

        $user->password = password_hash($params['password'], PASSWORD_DEFAULT);

        if ($user->update())
            return self::success($user->uid);

        return self::error('Не удалось обновить пароль', 400);
    }

    public static function index(): array
    {
        return ["POST" => "[Пользователь] Обновить пароль"];
    }
}