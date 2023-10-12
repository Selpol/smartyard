<?php

namespace Selpol\Controller\Api\subscribers;

use Selpol\Controller\Api\api;
use Selpol\Feature\House\HouseFeature;

class subscribers extends api
{
    public static function GET(array $params): array
    {
        $households = container(HouseFeature::class);

        $flat = [
            "subscribers" => $households->getSubscribers(@$params["by"], @$params["query"]),
            "cameras" => $households->getCameras(@$params["by"], @$params["query"]),
            "keys" => $households->getKeys(@$params["by"], @$params["query"]),
        ];

        return api::ANSWER($flat, $flat ? "flat" : false);
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Абоненты] Получить список'];
    }
}
