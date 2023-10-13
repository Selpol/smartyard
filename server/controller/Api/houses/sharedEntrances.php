<?php

namespace Selpol\Controller\Api\houses;

use Selpol\Controller\Api\Api;
use Selpol\Feature\House\HouseFeature;

class sharedEntrances extends Api
{
    public static function GET(array $params): array
    {
        $households = container(HouseFeature::class);

        $entrances = $households->getSharedEntrances(array_key_exists('_id', $params) ? $params['_id'] : false);

        return Api::ANSWER($entrances, ($entrances !== false) ? 'entrances' : 'notAcceptable');
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Дом] Получить общий вход'];
    }
}
