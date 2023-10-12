<?php

namespace Selpol\Controller\Api\houses;

use Selpol\Controller\Api\api;
use Selpol\Feature\House\HouseFeature;
use Selpol\Task\Tasks\Intercom\IntercomEntranceTask;

class flatEntrance extends api
{
    public static function POST(array $params): array
    {
        $entrance = intval($params['_id']);

        $house = container(HouseFeature::class);

        foreach ($params['flats'] as $flat)
            $house->addEntranceToFlat($entrance, intval($flat['flatId']), intval($flat['apartment']));

        task(new IntercomEntranceTask($entrance))->high()->dispatch();

        return self::ANSWER();
    }

    public static function index(): array
    {
        return ['POST' => '[Дом] Привязать вход к квартирам'];
    }
}