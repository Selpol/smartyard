<?php

namespace Selpol\Controller\Api\houses;

use Selpol\Controller\Api\api;
use Selpol\Feature\House\HouseFeature;

class cameras extends api
{

    public static function POST(array $params): array
    {
        $households = container(HouseFeature::class);

        $cameraId = $households->addCamera("house", $params["houseId"], $params["cameraId"]);

        return api::ANSWER($cameraId, ($cameraId !== false) ? "cameraId" : false);
    }

    public static function DELETE(array $params): array
    {
        $households = container(HouseFeature::class);

        $success = $households->unlinkCamera("house", $params["houseId"], $params["cameraId"]);

        return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
    }

    public static function index(): bool|array
    {
        return [
            "POST" => "[Дом] Привязать камеру",
            "DELETE" => "[Дом] Отвязать камеру",
        ];
    }
}
