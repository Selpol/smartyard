<?php

namespace Selpol\Controller\Api\intercom;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\Device\DeviceIntercom;
use Selpol\Framework\Entity\EntityPage;
use Selpol\Service\AuthService;

readonly class intercoms extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $validate = validator($params, [
            'comment' => rule()->string()->clamp(0, 1000),

            'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
            'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
        ]);

        $criteria = criteria()->like('comment', $validate['comment'])->asc('house_domophone_id');

        if (!container(AuthService::class)->checkScope('intercom-hidden'))
            $criteria->equal('hidden', false);

        $page = DeviceIntercom::fetchPage($validate['page'], $validate['size'], $criteria);

        $result = [];

        foreach ($page->getData() as $data)
            $result[] = $data->toArrayMap([
                'house_domophone_id' => 'domophoneId',
                'enabled' => 'enabled',
                'model' => 'model',
                'server' => 'server',
                'url' => 'url',
                'credentials' => 'credentials',
                'dtmf' => 'dtmf',
                'first_time' => 'firstTime',
                'nat' => 'nat',
                'comment' => 'comment',
                'ip' => 'ip',
                'sos_number' => 'sosNumber',
                'hidden' => 'hidden'
            ]);

        return self::success(new EntityPage($result, $page->getTotal(), $page->getPage(), $page->getSize()));
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Домофон] Получить список домофонов'];
    }
}