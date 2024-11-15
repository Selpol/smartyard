<?php declare(strict_types=1);

namespace Selpol\Controller;

use Psr\Http\Message\ResponseInterface;
use Selpol\Framework\Http\Response;
use Selpol\Service\Auth\AuthTokenInterface;
use Selpol\Service\Auth\User\CoreAuthUser;
use Selpol\Service\AuthService;

readonly abstract class AdminRbtController extends RbtController
{
    protected function getToken(): AuthTokenInterface
    {
        return container(AuthService::class)->getTokenOrThrow();
    }

    protected function getUser(): CoreAuthUser
    {
        return container(AuthService::class)->getUserOrThrow();
    }

    public static function success(mixed $data = null, int $code = 200): ResponseInterface
    {
        return json_response($code, body: $data === null ? ['success' => true] : ['success' => true, 'data' => $data]);
    }

    public static function error(?string $message = null, int $code = 500): ResponseInterface
    {
        return json_response(
            $code,
            body: [
                'success' => false,
                'message' => $message ?? (array_key_exists($code, Response::$codes) ? Response::$codes[$code]['name'] : 'Неизвестная ошибка')
            ]
        );
    }
}