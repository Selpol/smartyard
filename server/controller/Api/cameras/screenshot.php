<?php

namespace Selpol\Controller\Api\cameras;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;

readonly class screenshot extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $device = camera($params['_id']);

        if (!$device->ping())
            return self::error('Устройство не доступно', 400);

        return response(headers: ['Content-Type' => ['image/jpeg']])->withBody($device->getScreenshot());
    }

    public static function index(): array
    {
        return ['GET' => '[Камера] Получить скриншот'];
    }
}