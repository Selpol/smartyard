<?php

namespace Selpol\Controller\Api\intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Device\Ip\Intercom\Setting\Cms\CmsInterface;

readonly class level extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $intercom = intercom(intval(rule()->id()->onItem('_id', $params)));

        if ($intercom instanceof CmsInterface) {
            if (array_key_exists('apartment', $params))
                return self::success(['resist' => $intercom->getLineDialStatus($params['apartment'])]);
            else if (array_key_exists('from', $params) && array_key_exists('to', $params))
                return self::success($intercom->getAllLineDialStatus(intval($params['from']), intval($params['to'])));

            return self::error('Не достаточно данных', 400);
        }

        return self::error('Устройство не поддерживает КМС', 400);
    }

    public static function index(): array
    {
        return ['GET' => '[Домофон] Запросить уровень'];
    }
}