<?php

namespace api\intercom;

use api\api;

class call extends api
{
    public static function GET($params)
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