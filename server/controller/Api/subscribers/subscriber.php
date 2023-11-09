<?php

namespace Selpol\Controller\Api\subscribers;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\House\HouseFeature;

readonly class subscriber extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        $subscribers = $households->getSubscribers('id', $params['_id']);

        if ($subscribers && count($subscribers) === 1) {
            $subscriber = $subscribers[0];

            $subscriber['mobile'] = mobile_mask($subscriber['mobile']);

            return self::success($subscriber);
        }

        return self::error('Абонент не найден', 400);
    }

    public static function POST(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        $subscriberId = $households->addSubscriber($params['mobile'], @$params['subscriberName'], @$params['subscriberPatronymic'], null, array_key_exists('flatId', $params) ? intval($params['flatId']) : null, @$params['message']);

        if ($subscriberId)
            return self::success($subscriberId);

        return self::error('Не удалось создать абонента', 400);
    }

    public static function PUT(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        $success = $households->modifySubscriber($params['_id'], $params)
            && $households->setSubscriberFlats($params['_id'], $params['flats']);

        if ($success)
            return self::success($params['_id']);

        return self::error('Не удалось обновить абонента', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        if (array_key_exists('force', $params) && $params['force']) {
            if (container(HouseFeature::class)->deleteSubscriber($params['subscriberId']))
                return self::success();

            return self::error('Не удалось удалить абонента', 400);
        }

        if (container(HouseFeature::class)->removeSubscriberFromFlat($params['_id'], $params['subscriberId']))
            return self::success();

        return self::error('Не удалось удалить абонента из квартиры', 400);
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Абоненты] Получить абонента', 'PUT' => '[Абоненты] Обновить абонента', 'POST' => '[Абоненты] Создать абонента', 'DELETE' => '[Абоненты] Удалить абонента'];
    }
}