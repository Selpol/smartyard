<?php

namespace Selpol\Controller\Api;

use Psr\Http\Message\ResponseInterface;
use Selpol\Framework\Http\Response;

readonly abstract class Api
{
    private function __construct()
    {
    }

    public static function GET(array $params): array|Response|ResponseInterface
    {
        return self::error('Метода не существует', 404);
    }

    public static function POST(array $params): array|Response|ResponseInterface
    {
        return self::error('Метода не существует', 404);
    }

    public static function PUT(array $params): array|Response|ResponseInterface
    {
        return self::error('Метода не существует', 404);
    }

    public static function DELETE(array $params): array|Response|ResponseInterface
    {
        return self::error('Метода не существует', 404);
    }

    public static function success(mixed $data = null, int $code = 200): ResponseInterface
    {
        return json_response(
            $code,
            body: $data === null ? ['success' => true] : ['success' => true, 'data' => $data]
        );
    }

    public static function error(?string $message = null, int $code = 500): ResponseInterface
    {
        return json_response(
            $code,
            body: [
                'success' => false,
                'message' => $message ?? (array_key_exists($code, Response::$codes) ? Response::$codes[$code] : 'Неизвестная ошибка')
            ]
        );
    }

    /**
     * internal function for indexing methods
     *
     * @return boolean|string[]
     */
    public static function index(): array|bool
    {
        return false;
    }
}