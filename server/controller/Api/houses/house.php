<?php

namespace Selpol\Controller\Api\houses;

use Selpol\Controller\Api\api;
use Selpol\Device\Ip\Intercom\IntercomCms;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Feature\House\HouseFeature;
use Selpol\Task\Tasks\Intercom\Key\IntercomHouseKeyTask;

class house extends api
{
    public static function GET(array $params): array
    {
        $households = container(HouseFeature::class);

        $flats = $households->getFlats("houseId", $params["_id"]);

        if ($flats)
            usort($flats, static fn(array $a, array $b) => $a['flat'] > $b['flat'] ? 1 : -1);

        $house = [
            "flats" => $flats,
            "entrances" => $households->getEntrances("houseId", $params["_id"]),
            "cameras" => $households->getCameras("houseId", $params["_id"]),
            "domophoneModels" => IntercomModel::modelsToArray(),
            "cmses" => IntercomCms::modelsToArray(),
        ];

        $house = ($house["flats"] !== false && $house["entrances"] !== false && $house["domophoneModels"] !== false && $house["cmses"] !== false) ? $house : false;

        return api::ANSWER($house, "house");
    }

    public static function POST(array $params): array
    {
        $households = container(HouseFeature::class);

        $houseId = $params['_id'];
        $keys = $params['keys'];

        foreach ($keys as $key) {
            $households->addKey($key["rfId"], 2, $key["accessTo"], '');
        }

        task(new IntercomHouseKeyTask($houseId));

        return api::ANSWER();
    }

    public static function index(): bool|array
    {
        return [
            "GET" => "[Дом] Получить дом",
            "POST" => "[Дом] Загрузить ключи"
        ];
    }
}
