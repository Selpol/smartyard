<?php

namespace Selpol\Controller\Api\accounts;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Core\CoreUser;
use Selpol\Feature\User\UserFeature;

readonly class user extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $user = container(UserFeature::class)->getUser($params["_id"]);

        if ($user) {
            if ($user['phone'])
                $user['phone'] = mobile_mask($user['phone']);

            return self::success($user);
        }

        return self::error('Пользователь не найден', 404);
    }

    public static function POST(array $params): ResponseInterface
    {
        $user = new CoreUser();

        $user->login = $params['login'];
        $user->password = password_hash(generate_password(), PASSWORD_DEFAULT);

        $user->real_name = $params['realName'];
        $user->e_mail = $params['eMail'];
        $user->phone = $params['phone'];

        if ($user->insert())
            return self::success($user->uid);

        return self::error('Не удалось создать пользователя', 400);
    }

    public static function PUT(array $params): ResponseInterface
    {
        if (!array_key_exists('realName', $params) && array_key_exists('enabled', $params)) {
            $user = CoreUser::findById($params['_id'], setting: setting()->nonNullable());

            $user->enabled = $params['enabled'];

            $success = container(UserFeature::class)->modifyUserEnabled($params['_id'], $params['enabled']);

            if ($success)
                return self::success($user->uid);

            return self::error('Не удалось обновить пользователя', 400);
        }

        $success = container(UserFeature::class)->modifyUser($params["_id"], $params["realName"], $params["eMail"], $params["phone"], $params["tg"], $params["notification"], $params["enabled"], $params["defaultRoute"]);

        if (@$params["password"] && (int)$params["_id"])
            $success = $success && container(UserFeature::class)->setPassword($params["_id"], $params["password"]);

        if ($success)
            return self::success($params['_id']);

        return self::error('Не удалось обновить пользователя', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $user = CoreUser::findById($params['_id'], setting: setting()->nonNullable());

        return $user->delete() ? self::success() : self::error('Не удалось удалить пользователя', 400);
    }

    public static function index(): array
    {
        return ["GET" => "[Пользователь] Получить пользователя", "POST" => '[Пользователь] Создать пользователя', "PUT" => "[Пользователь] Обновить пользователя", "DELETE" => '[Пользователь] Удалить пользователя'];
    }
}