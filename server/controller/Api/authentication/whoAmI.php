<?php

namespace Selpol\Controller\Api\authentication;

use Selpol\Controller\Api\api;
use Selpol\Feature\Sip\SipFeature;
use Selpol\Feature\User\UserFeature;
use Selpol\Service\RedisService;

class whoAmI extends api
{
    public static function GET(array $params): array
    {
        $user = container(UserFeature::class)->getUser($params["_uid"]);

        $extension = sprintf("7%09d", (int)$params["_uid"]);

        $redis = container(RedisService::class)->getConnection();

        $cred = $redis->get("webrtc_" . md5($extension));

        if (!$cred)
            $cred = md5(guid_v4());

        $redis->setex("webrtc_" . md5($extension), 24 * 60 * 60, $cred);

        $user['webRtcDomain'] = container(SipFeature::class)->server('first')['ip'];
        $user["webRtcExtension"] = $extension;
        $user["webRtcPassword"] = $cred;

        return api::ANSWER($user, ($user !== false) ? "user" : "notFound");
    }

    public static function index(): bool|array
    {
        return ["GET" => "[Авторизация] Запросить дополнительные данные"];
    }
}
