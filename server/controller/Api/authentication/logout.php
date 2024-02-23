<?php

namespace Selpol\Controller\Api\authentication;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Authentication\AuthenticationFeature;
use Selpol\Service\Auth\Token\CoreAuthToken;
use Selpol\Service\AuthService;

readonly class logout extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $token = container(AuthService::class)->getTokenOrThrow();

        if ($token instanceof CoreAuthToken)
            container(AuthenticationFeature::class)->logout($token->getOriginalValue());

        return self::success();
    }

    public static function POST(array $params): ResponseInterface
    {
        container(AuthenticationFeature::class)->logout($params['session']);

        return self::success();
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Авторизация] Выйти', 'POST' => '[Авторизация] Разлогинить пользователя'];
    }
}