<?php

namespace Selpol\Controller\Api\dvr;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;

readonly class cameras extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        if ($cameras = dvr(rule()->id()->onItem('_id', $params))?->getCameras()) {
            usort($cameras, self::sort(...));

            return self::success($cameras);
        }

        return self::success([]);
    }

    public static function index(): array|bool
    {
        return ['GET' => '[Dvr] Получить список камер на сервере'];
    }

    private static function sort(array $a, array $b): int
    {
        return strcmp($a['title'], $b['title']);
    }
}