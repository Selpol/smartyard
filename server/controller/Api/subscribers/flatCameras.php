<?php

namespace Selpol\Controller\Api\subscribers;

use Selpol\Controller\Api\Api;
use Selpol\Feature\House\HouseFeature;

class flatCameras extends Api
{
    public static function POST(array $params): array
    {
        $households = container(HouseFeature::class);

        $cameraId = $households->addCamera("flat", $params["flatId"], $params["cameraId"]);

        return Api::ANSWER($cameraId, ($cameraId !== false) ? "cameraId" : "notAcceptable");
    }

    public static function DELETE(array $params): array
    {
        $households = container(HouseFeature::class);

        $success = $households->unlinkCamera("flat", $params["flatId"], $params["cameraId"]);

        return Api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
    }

    public static function index(): bool|array
    {
        return [
            "POST" => "[Квартира] Привязать камеру",
            "DELETE" => "[Квартира] Отвязать камеру",
        ];
    }
}