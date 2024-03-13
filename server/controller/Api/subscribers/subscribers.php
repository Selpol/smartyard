<?php

namespace Selpol\Controller\Api\subscribers;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\House\HouseSubscriber;
use Selpol\Feature\House\HouseFeature;

readonly class subscribers extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        if ($params['by'] === 'name') {
            $validate = validator($params, [
                'name' => rule()->string()->clamp(0, 1000),

                'page' => [filter()->default(0), rule()->required()->int()->clamp(0)->nonNullable()],
                'size' => [filter()->default(10), rule()->required()->int()->clamp(1, 1000)->nonNullable()]
            ]);

            $criteria = criteria()->like('subscriber_name', $validate['name'])->orLike('subscriber_patronymic', $validate['name'])->asc('house_subscriber_id');

            return self::success(HouseSubscriber::fetchPage($validate['page'], $validate['size'], $criteria, setting()->columns(['house_subscriber_id', 'subscriber_name', 'subscriber_patronymic'])));
        }

        $households = container(HouseFeature::class);

        $flat = [
            'subscribers' => array_map(static function (array $item) {
                $item['mobile'] = mobile_mask($item['mobile']);

                return $item;
            }, $households->getSubscribers(@$params['by'], @$params['query'])),
            'cameras' => $households->getCameras(@$params['by'], @$params['query']),
            'keys' => $households->getKeys(@$params['by'], @$params['query']),
        ];

        if ($flat)
            return self::success($flat);

        return self::error('Не удалось получить квартиру', 400);
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Абоненты] Получить список'];
    }
}