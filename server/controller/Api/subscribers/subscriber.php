<?php

namespace Selpol\Controller\Api\subscribers;

use Selpol\Controller\Api\Api;
use Selpol\Feature\House\HouseFeature;

class subscriber extends Api
{
    public static function GET(array $params): array
    {
        $households = container(HouseFeature::class);

        $subscribers = $households->getSubscribers('id', $params['_id']);

        if ($subscribers && count($subscribers) === 1)
            return Api::ANSWER($subscribers[0]);

        return Api::ERROR();
    }

    public static function POST(array $params): array
    {
        $households = container(HouseFeature::class);

        $subscriberId = $households->addSubscriber($params['mobile'], @$params['subscriberName'], @$params['subscriberPatronymic'], null, array_key_exists('flatId', $params) ? intval($params['flatId']) : null, @$params['message']);

        return Api::ANSWER($subscriberId, ($subscriberId !== false) ? 'subscriber' : false);
    }

    public static function PUT(array $params): array
    {
        $households = container(HouseFeature::class);

        $success = $households->modifySubscriber($params['_id'], $params)
            && $households->setSubscriberFlats($params['_id'], $params['flats']);

        return Api::ANSWER($success);
    }

    public static function DELETE(array $params): array
    {
        if (array_key_exists('force', $params) && $params['force'])
            return Api::ANSWER(container(HouseFeature::class)->deleteSubscriber($params['subscriberId']));

        return Api::ANSWER(container(HouseFeature::class)->removeSubscriberFromFlat($params['_id'], $params['subscriberId']));
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Абоненты] Получить абонента', 'PUT' => '[Абоненты] Обновить абонента', 'POST' => '[Абоненты] Создать абонента', 'DELETE' => '[Абоненты] Удалить абонента'];
    }
}