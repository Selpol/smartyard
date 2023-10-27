<?php

namespace Selpol\Controller\Api\cameras;

use Selpol\Controller\Api\Api;
use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Entity\Model\Frs\FrsServer;
use Selpol\Feature\Camera\CameraFeature;
use Selpol\Feature\Frs\FrsFeature;

readonly class cameras extends Api
{
    public static function GET(array $params): array
    {
        $response = [
            "cameras" => container(CameraFeature::class)->getCameras(),
            "models" => CameraModel::modelsToArray(),
            "frsServers" => array_map(static fn(FrsServer $server) => $server->toArrayMap(['title' => 'title', 'url' => 'url']), container(FrsFeature::class)->servers()),
        ];

        return Api::ANSWER($response, "cameras");
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Камера] Получить список'];
    }
}