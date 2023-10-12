<?php

namespace Selpol\Controller\Api\subscribers;

use Selpol\Controller\Api\api;
use Selpol\Feature\House\HouseFeature;

class subscriber extends api
{
    public static function GET(array $params): array
    {
        $households = container(HouseFeature::class);

        $subscribers = $households->getSubscribers('id', $params['_id']);

        if ($subscribers && count($subscribers) === 1)
            return api::ANSWER($subscribers[0]);

        return api::ERROR();
    }

    public static function POST(array $params): array
    {
        $households = container(HouseFeature::class);

        $subscriberId = $households->addSubscriber($params["mobile"], @$params["subscriberName"], @$params["subscriberPatronymic"], null, array_key_exists('flatId', $params) ? intval($params['flatId']) : null, @$params["message"]);

        return api::ANSWER($subscriberId, ($subscriberId !== false) ? "subscriber" : false);
    }

    public static function PUT(array $params): array
    {
        $households = container(HouseFeature::class);

        $success = $households->modifySubscriber($params["_id"], $params)
            && $households->setSubscriberFlats($params["_id"], $params["flats"]);

        return api::ANSWER($success);
    }

    public static function DELETE(array $params): array
    {
        if (array_key_exists('force', $params) && $params['force'])
            return api::ANSWER(container(HouseFeature::class)->deleteSubscriber($params['subscriberId']));

        return api::ANSWER(container(HouseFeature::class)->removeSubscriberFromFlat($params["_id"], $params["subscriberId"]));
    }

    public static function index(): bool|array
    {
        return [
            "GET" => "[Абоненты] Получить абонента",
            "PUT" => "[Абоненты] Обновить абонента",
            "POST" => "[Абоненты] Создать абонента",
            "DELETE" => "[Абоненты] Удалить абонента",
        ];
    }
}