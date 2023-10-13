<?php

namespace Selpol\Controller\Api\authentication;

use RedisException;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Authentication\AuthenticationFeature;
use Selpol\Service\Auth\Token\RedisAuthToken;
use Selpol\Service\AuthService;

class logout extends Api
{
    /**
     * @throws RedisException
     */
    public static function GET(array $params): array
    {
        $token = container(AuthService::class)->getTokenOrThrow();

        if ($token instanceof RedisAuthToken)
            container(AuthenticationFeature::class)->logout($token->getOriginalValue());

        return ['204' => null];
    }

    /**
     * @throws RedisException
     */
    public static function POST(array $params): array
    {
        container(AuthenticationFeature::class)->logout($params["_token"], @$params['mode'] == 'all');

        return ['204' => null];
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Авторизация] Выйти', 'POST' => '[Авторизация] Разлогинить пользователя'];
    }
}