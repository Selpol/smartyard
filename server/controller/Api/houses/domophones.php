<?php

namespace Selpol\Controller\Api\houses;

use Selpol\Controller\Api\Api;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Feature\House\HouseFeature;

readonly class domophones extends Api
{
    public static function GET(array $params): array
    {
        $households = container(HouseFeature::class);

        if (!$households) return Api::FALSE();
        else {
            $response = ['domophones' => $households->getDomophones(), 'models' => IntercomModel::modelsToArray()];

            return Api::ANSWER($response, 'domophones');
        }
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Дом] Получить список домофонов'];
    }
}