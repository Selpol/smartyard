<?php

namespace Selpol\Controller\Api\intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;

readonly class open extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $validate = validator($params, [
            '_id' => rule()->id(),

            'output' => rule()->int()->clamp(0, 10)
        ]);

        $device = intercom($validate['_id']);

        if (!$device->ping()) {
            return self::error('Устройство не доступно', 400);
        }

        $device->open($validate['output']);

        return self::success();
    }

    public static function index(): array
    {
        return ['GET' => '[Домофон] Открыть дверь'];
    }
}