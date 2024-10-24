<?php

namespace Selpol\Controller\Api\accounts;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Core\CoreUser;
use Selpol\Feature\Oauth\OauthFeature;
use Selpol\Feature\User\UserFeature;
use Selpol\Service\AuthService;
use Selpol\Service\RedisService;
use Throwable;

readonly class user extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $user = container(UserFeature::class)->getUser($params["_id"]);

        if ($user) {
            if ($user['phone'] && !container(AuthService::class)->checkScope('mobile-mask')) {
                $user['phone'] = mobile_mask($user['phone']);
            }

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

        try {
            $id = container(OauthFeature::class)->register($params['phone']);

            if ($id) {
                $user->aud_jti = $id;
            }
        } catch (Throwable) {
        }

        if ($user->safeInsert()) {
            return self::success($user->uid);
        }

        return self::error('Не удалось создать пользователя', 400);
    }

    public static function PUT(array $params): ResponseInterface
    {
        $user = CoreUser::findById(rule()->id()->onItem('_id', $params), setting: setting()->nonNullable());

        if (!array_key_exists('realName', $params) && array_key_exists('enabled', $params)) {
            $user->enabled = $params['enabled'];

            if ($user->safeUpdate()) {
                if (!$user->enabled) {
                    $keys = container(RedisService::class)->keys('user:' . $user->uid . ':token:*');

                    if (count($keys) > 0) {
                        container(RedisService::class)->del(...$keys);
                    }
                }

                return self::success($user->uid);
            }

            return self::error('Не удалось обновить пользователя', 400);
        }

        $user->real_name = $params['realName'];
        $user->e_mail = $params['eMail'];

        if (!str_contains($params['phone'], '*')) {
            $user->phone = $params['phone'];

            try {
                $id = container(OauthFeature::class)->register($params['phone']);

                if ($id) {
                    $user->aud_jti = $id;
                }
            } catch (Throwable) {
            }
        }

        $user->tg = $params['tg'];
        $user->notification = $params['notification'];
        $user->enabled = $params['enabled'];
        $user->default_route = $params['defaultRoute'];

        if (array_key_exists('password', $params)) {
            $user->password = password_hash($params['password'], PASSWORD_DEFAULT);
        }

        if ($user->safeUpdate()) {
            if (!$user->enabled) {
                $keys = container(RedisService::class)->keys('user:' . $user->uid . ':token:*');

                if (count($keys) > 0) {
                    container(RedisService::class)->del(...$keys);
                }
            }

            return self::success($user->uid);
        }

        return self::error('Не удалось обновить пользователя', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $user = CoreUser::findById($params['_id'], setting: setting()->nonNullable());

        return $user->safeDelete() ? self::success() : self::error('Не удалось удалить пользователя', 400);
    }

    public static function index(): array
    {
        return ["GET" => "[Пользователь] Получить пользователя", "POST" => '[Пользователь] Создать пользователя', "PUT" => "[Пользователь] Обновить пользователя", "DELETE" => '[Пользователь] Удалить пользователя'];
    }
}