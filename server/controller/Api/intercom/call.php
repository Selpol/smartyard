<?php

namespace Selpol\Controller\Api\intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;

readonly class call extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $device = intercom($params['_id']);

        if (!$device->ping())
            return self::error('Устройство не доступно', 400);

        $device->call($params['apartment']);

        return self::success();
    }

    public static function index(): array
    {
        return ['GET' => '[Домофон] Звонок в квартиру'];
    }
}