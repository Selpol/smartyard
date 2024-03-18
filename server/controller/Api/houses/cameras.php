<?php

namespace Selpol\Controller\Api\houses;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\House\HouseFeature;

readonly class cameras extends Api
{
    public static function POST(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        $success = $households->addCamera('house', $params['houseId'], $params['cameraId']);

        if ($success !== false)
            return self::success();

        return self::error('Не удалось привязать камеру к дому', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        $success = $households->unlinkCamera('house', $params['houseId'], $params['cameraId']);

        if ($success)
            return self::success();

        return self::error('Не удалось отвязать камеру от дома', 400);
    }

    public static function index(): bool|array
    {
        return ['POST' => '[Дом] Привязать камеру', 'DELETE' => '[Дом] Отвязать камеру'];
    }
}
