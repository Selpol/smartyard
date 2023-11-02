<?php

namespace Selpol\Controller\Api\subscribers;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\House\HouseFeature;

readonly class subscriberCameras extends Api
{
    public static function POST(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        $cameraId = $households->addCamera('subscriber', $params['subscriberId'], $params['cameraId']);

        if ($cameraId)
            return self::success($cameraId);

        return self::error('Не удалось добавить камеру абоненту', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        $success = $households->unlinkCamera('subscriber', $params['subscriberId'], $params['cameraId']);

        if ($success)
            return self::success();

        return self::error('Не удалось удалить камеру у абонента', 400);
    }

    public static function index(): bool|array
    {
        return ['POST' => '[Абоненты] Привязать камеру', 'DELETE' => '[Абоненты] Отвязать камеру'];
    }
}