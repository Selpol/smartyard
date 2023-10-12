<?php

namespace Selpol\Controller\Api\houses;

use Selpol\Controller\Api\api;
use Selpol\Feature\House\HouseFeature;

class sharedEntrances extends api
{
    public static function GET(array $params): array
    {
        $households = container(HouseFeature::class);

        $entrances = $households->getSharedEntrances(@$params["_id"]);

        return api::ANSWER($entrances, ($entrances !== false) ? "entrances" : "notAcceptable");
    }

    public static function index(): bool|array
    {
        return [
            "GET" => "[Дом] Получить общий вход"
        ];
    }
}
