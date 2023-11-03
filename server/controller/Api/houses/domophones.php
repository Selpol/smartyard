<?php

namespace Selpol\Controller\Api\houses;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Feature\House\HouseFeature;

readonly class domophones extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        return self::success(['domophones' => $households->getDomophones(), 'models' => IntercomModel::modelsToArray()]);
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Дом] Получить список домофонов'];
    }
}