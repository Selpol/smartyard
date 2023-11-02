<?php

namespace Selpol\Controller\Api\authentication;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;

readonly class ping extends Api
{
    public static function POST(array $params): ResponseInterface
    {
        return self::success();
    }

    public static function index(): bool|array
    {
        return ['POST' => '[Авторизация] Проверка доступности'];
    }
}