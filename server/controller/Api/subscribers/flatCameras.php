<?php

namespace Selpol\Controller\Api\subscribers;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\House\HouseFeature;

readonly class flatCameras extends Api
{
    public static function POST(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        $cameraId = $households->addCamera('flat', $params['flatId'], $params['cameraId']);

        if ($cameraId)
            return self::success($cameraId);

        return self::error('Не удалось добавить камеру в квартиру', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        $success = $households->unlinkCamera('flat', $params['flatId'], $params['cameraId']);

        if ($success)
            return self::success();

        return self::error('Не удалось удалить камеру из квартиры', 400);
    }

    public static function index(): bool|array
    {
        return ['POST' => '[Квартира] Привязать камеру', 'DELETE' => '[Квартира] Отвязать камеру'];
    }
}