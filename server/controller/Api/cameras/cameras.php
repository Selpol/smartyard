<?php

namespace Selpol\Controller\Api\cameras;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Device\Ip\Camera\CameraModel;
use Selpol\Feature\Camera\CameraFeature;

readonly class cameras extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        return self::success([
            "cameras" => container(CameraFeature::class)->getCameras(),
            "models" => CameraModel::modelsToArray()
        ]);
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Камера] Получить список'];
    }
}