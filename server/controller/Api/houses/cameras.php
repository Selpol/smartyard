<?php

namespace Selpol\Controller\Api\houses;

use Selpol\Controller\Api\Api;
use Selpol\Feature\House\HouseFeature;

class cameras extends Api
{
    public static function POST(array $params): array
    {
        $households = container(HouseFeature::class);

        $cameraId = $households->addCamera('house', $params['houseId'], $params['cameraId']);

        return Api::ANSWER($cameraId, ($cameraId !== false) ? 'cameraId' : false);
    }

    public static function DELETE(array $params): array
    {
        $households = container(HouseFeature::class);

        $success = $households->unlinkCamera('house', $params['houseId'], $params['cameraId']);

        return Api::ANSWER($success, ($success !== false) ? false : 'notAcceptable');
    }

    public static function index(): bool|array
    {
        return ['POST' => '[Дом] Привязать камеру', 'DELETE' => '[Дом] Отвязать камеру'];
    }
}
