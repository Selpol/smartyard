<?php

namespace Selpol\Controller\Api\subscribers;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\House\HouseFeature;

readonly class subscribers extends Api
{
    public static function GET(array $params): ResponseInterface
    {
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

        return self::error('Не удалось получить квартиру');
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Абоненты] Получить список'];
    }
}