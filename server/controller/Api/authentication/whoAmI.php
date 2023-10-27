<?php

namespace Selpol\Controller\Api\authentication;

use RedisException;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Sip\SipFeature;
use Selpol\Feature\User\UserFeature;
use Selpol\Service\RedisService;

readonly class whoAmI extends Api
{
    /**
     * @throws RedisException
     */
    public static function GET(array $params): array
    {
        $user = container(UserFeature::class)->getUser($params["_uid"]);

        $redis = container(RedisService::class)->getConnection();

        $password = $redis->get('user:' . $params['_uid'] . ':ws');

        if (!$password)
            $password = md5(guid_v4());

        $redis->setex('user:' . $params['_uid'] . ':ws', 24 * 60 * 60, $password);

        $sipServer = container(SipFeature::class)->server('first')[0];

        $user['wsTitle'] = $sipServer->title;
        $user['wsDomain'] = $sipServer->external_ip;

        $user["wsUsername"] = sprintf("7%09d", (int)$params["_uid"]);
        $user["wsPassword"] = $password;

        return Api::ANSWER($user, ($user !== false) ? "user" : "forbidden");
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Авторизация] Запросить дополнительные данные'];
    }
}