<?php

namespace Selpol\Controller\Api\intercom;

use Selpol\Controller\Api\Api;

class call extends Api
{
    public static function GET(array $params): array
    {
        $device = intercom($params['_id']);

        if (!$device->ping())
            return self::ERROR('Устройство не доступно');

        $device->call($params['apartment']);

        return self::ANSWER();
    }

    public static function index(): array
    {
        return ['GET' => '[Домофон] Звонок в квартиру'];
    }
}