<?php

namespace Selpol\Controller\Api\authentication;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Sip\SipFeature;
use Selpol\Feature\User\UserFeature;
use Selpol\Service\AuthService;
use Selpol\Service\RedisService;

readonly class whoAmI extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $user = container(UserFeature::class)->getUser(container(AuthService::class)->getUserOrThrow()->getIdentifier());

        if (!$user)
            return self::error('Пользователь не найден', 404);

        $redis = container(RedisService::class);

        $password = $redis->get('user:' . $params['_uid'] . ':ws');

        if (!$password)
            $password = md5(guid_v4());

        $redis->setEx('user:' . $params['_uid'] . ':ws', 24 * 60 * 60, $password);

        $sipServer = container(SipFeature::class)->server('first')[0];

        $user['wsTitle'] = $sipServer->title;
        $user['wsDomain'] = $sipServer->external_ip;

        $user["wsUsername"] = sprintf("7%09d", (int)$params["_uid"]);
        $user["wsPassword"] = $password;

        return self::success($user);
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Авторизация] Запросить дополнительные данные'];
    }
}