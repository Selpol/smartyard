<?php

namespace Selpol\Controller\Api\cameras;

use Selpol\Controller\Api\api;
use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Feature\Camera\CameraFeature;
use Selpol\Feature\Frs\FrsFeature;

class cameras extends api
{
    public static function GET(array $params): array
    {
        $response = [
            "cameras" => container(CameraFeature::class)->getCameras(),
            "models" => CameraModel::modelsToArray(),
            "frsServers" => container(FrsFeature::class)->servers(),
        ];

        return api::ANSWER($response, "cameras");
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Камера] Получить список'];
    }
}