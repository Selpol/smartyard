<?php

namespace Selpol\Controller\Api\houses;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\House\HouseFeature;

readonly class sharedEntrances extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        $entrances = $households->getSharedEntrances(array_key_exists('_id', $params) ? $params['_id'] : false);

        return $entrances ? self::success($entrances) : self::error('Общие входы не найден', 404);
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Дом] Получить общий вход'];
    }
}