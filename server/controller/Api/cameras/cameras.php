<?php

namespace Selpol\Controller\Api\cameras;

use Selpol\Controller\Api\Api;
use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Feature\Camera\CameraFeature;

readonly class cameras extends Api
{
    public static function GET(array $params): array
    {
        $response = [
            "cameras" => container(CameraFeature::class)->getCameras(),
            "models" => CameraModel::modelsToArray()
        ];

        return Api::ANSWER($response, "cameras");
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Камера] Получить список'];
    }
}