<?php

namespace Selpol\Controller\Api\authentication;

use Psr\Http\Message\ResponseInterface;
use RedisException;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Authentication\AuthenticationFeature;
use Selpol\Service\Auth\Token\RedisAuthToken;
use Selpol\Service\AuthService;

readonly class logout extends Api
{
    /**
     * @throws RedisException
     */
    public static function GET(array $params): ResponseInterface
    {
        $token = container(AuthService::class)->getTokenOrThrow();

        if ($token instanceof RedisAuthToken)
            container(AuthenticationFeature::class)->logout($token->getOriginalValue());

        return self::success();
    }

    /**
     * @throws RedisException
     */
    public static function POST(array $params): ResponseInterface
    {
        container(AuthenticationFeature::class)->logout($params["_token"], @$params['mode'] == 'all');

        return self::success();
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Авторизация] Выйти', 'POST' => '[Авторизация] Разлогинить пользователя'];
    }
}