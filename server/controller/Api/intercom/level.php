<?php

namespace Selpol\Controller\Api\intercom;

use Selpol\Controller\Api\Api;
use Selpol\Service\DeviceService;

class level extends Api
{
    public static function GET(array $params): array
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
        return ["GET" => "[Домофон] Запросить уровень"];
    }
}