<?php

namespace Selpol\Controller\Api\accounts;

use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Core\CoreUser;
use Selpol\Entity\Repository\Core\CoreUserRepository;
use Selpol\Feature\Authentication\AuthenticationFeature;
use Selpol\Feature\User\UserFeature;

readonly class user extends Api
{
    public static function GET(array $params): array
    {
        $user = container(UserFeature::class)->getUser($params["_id"]);

        return Api::ANSWER($user, ($user !== false) ? "user" : "notFound");
    }

    public static function POST(array $params): array
    {
        $user = new CoreUser();

        $user->login = $params['login'];
        $user->password = password_hash(generate_password(), PASSWORD_DEFAULT);

        $user->real_name = $params['realName'];
        $user->e_mail = $params['eMail'];
        $user->phone = $params['phone'];

        $success = container(CoreUserRepository::class)->insert($user);

        return self::ANSWER($success ? $user->uid : false, $success ? 'uid' : 'notAcceptable');
    }

    public static function PUT(array $params): array
    {
        if (!array_key_exists('realName', $params) && array_key_exists('enabled', $params)) {
            $user = container(CoreUserRepository::class)->findById($params['_id']);

            $user->enabled = $params['enabled'];

            $success = container(UserFeature::class)->modifyUserEnabled($params['_id'], $params['enabled']);

            return self::ANSWER($success, ($success !== false) ? false : "notAcceptable");
        }

        $success = container(UserFeature::class)->modifyUser($params["_id"], $params["realName"], $params["eMail"], $params["phone"], $params["tg"], $params["notification"], $params["enabled"], $params["defaultRoute"]);

        if (@$params["password"] && (int)$params["_id"]) {
            $success = $success && container(UserFeature::class)->setPassword($params["_id"], $params["password"]);

            return self::ANSWER($success, ($success !== false) ? false : "notAcceptable");
        } else return Api::ANSWER($success, ($success !== false) ? false : "notAcceptable");

    }

    public static function DELETE(array $params): array
    {
        if (@$params["session"]) {
            container(AuthenticationFeature::class)->logout($params["session"]);

            $success = true;
        } else {
            $user = container(CoreUserRepository::class)->findById($params['_id']);

            $success = container(CoreUserRepository::class)->delete($user);
        }

        return Api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
    }

    public static function index(): array
    {
        return ["GET" => "[Пользователь] Получить пользователя", "POST" => '[Пользователь] Создать пользователя', "PUT" => "[Пользователь] Обновить пользователя", "DELETE" => '[Пользователь] Удалить пользователя'];
    }
}