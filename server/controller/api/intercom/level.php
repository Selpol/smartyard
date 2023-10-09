<?php

namespace api\intercom;

use api\api;
use Selpol\Service\DeviceService;

class level extends api
{
    public static function GET($params)
    {
        $intercom = container(DeviceService::class)->intercomById($params['_id']);

        if (array_key_exists('apartment', $params))
            return self::SUCCESS('level', ['resist' => intval($intercom->getLineDialStatus($params['apartment']))]);
        else if (array_key_exists('from', $params) && array_key_exists('to', $params))
            return self::SUCCESS('levels', $intercom->getAllLineDialStatus(intval($params['from']), intval($params['to'])));

        return self::ERROR();
    }

    public static function index(): array
    {
        return ["GET" => "#same(intercom,level,GET)"];
    }
}