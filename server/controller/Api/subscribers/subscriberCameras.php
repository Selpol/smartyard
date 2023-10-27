<?php

namespace Selpol\Controller\Api\subscribers;

use Selpol\Controller\Api\Api;
use Selpol\Feature\House\HouseFeature;

readonly class subscriberCameras extends Api
{
    public static function POST(array $params): array
    {
        $households = container(HouseFeature::class);

        $cameraId = $households->addCamera('subscriber', $params['subscriberId'], $params['cameraId']);

        return Api::ANSWER($cameraId, ($cameraId !== false) ? 'cameraId' : 'notAcceptable');
    }

    public static function DELETE(array $params): array
    {
        $households = container(HouseFeature::class);

        $success = $households->unlinkCamera('subscriber', $params['subscriberId'], $params['cameraId']);

        return Api::ANSWER($success, ($success !== false) ? false : 'notAcceptable');
    }

    public static function index(): bool|array
    {
        return ['POST' => '[Абоненты] Привязать камеру', 'DELETE' => '[Абоненты] Отвязать камеру'];
    }
}
