<?php

namespace Selpol\Controller\Api\accounts;

use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Core\CoreUser;
use Selpol\Entity\Repository\Core\CoreUserRepository;
use Selpol\Framework\Entity\EntitySetting;

readonly class password extends Api
{
    public static function POST(array $params): array
    {
        $user = CoreUser::findById($params['_id'], setting: setting()->nonNullable());

        $user->password = password_hash($params['password'], PASSWORD_DEFAULT);

        if ($user->update())
            return self::ANSWER($user->uid, 'userId');

        return self::FALSE('Неудалось обновить пароль');
    }

    public static function index(): array
    {
        return ["POST" => "[Пользователь] Обновить пароль"];
    }
}