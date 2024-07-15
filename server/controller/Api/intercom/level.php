<?php

namespace Selpol\Controller\Api\intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;

readonly class level extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $validate = validator($params, [
            '_id' => rule()->id(),

            'apartment' => rule()->int(),

            'from' => rule()->int(),
            'to' => rule()->int(),

            'info' => [filter()->default(false), rule()->required()->bool()->nonNullable()]
        ]);

        $intercom = intercom($validate['_id']);

        if (!is_null($validate['apartment']))
            return self::success($validate['info'] ? $intercom->getLineDialStatus($validate['apartment'], true) : ['resist' => $intercom->getLineDialStatus($validate['apartment'], false)]);
        else if (!is_null($validate['from']) && !is_null($validate['to']))
            return self::success($intercom->getAllLineDialStatus($validate['from'], $validate['to'], $validate['info']));

        return self::error('Не достаточно данных', 400);
    }

    public static function index(): array
    {
        return ['GET' => '[Домофон] Запросить уровень'];
    }
}