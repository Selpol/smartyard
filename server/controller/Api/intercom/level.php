<?php

namespace Selpol\Controller\Api\intercom;

use Selpol\Controller\Api\Api;

readonly class level extends Api
{
    public static function GET(array $params): array
    {
        $intercom = intercom(intval(rule()->id()->onItem('_id', $params)));

        if (array_key_exists('apartment', $params))
            return self::SUCCESS('level', ['resist' => $intercom->getLineDialStatus($params['apartment'])]);
        else if (array_key_exists('from', $params) && array_key_exists('to', $params))
            return self::SUCCESS('levels', $intercom->getAllLineDialStatus(intval($params['from']), intval($params['to'])));

        return self::ERROR('Данные не переданны');
    }

    public static function index(): array
    {
        return ['GET' => '[Домофон] Запросить уровень'];
    }
}