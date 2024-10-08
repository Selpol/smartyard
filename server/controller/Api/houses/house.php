<?php

namespace Selpol\Controller\Api\houses;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Device\Ip\Intercom\IntercomCms;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Feature\House\HouseFeature;
use Selpol\Service\AuthService;
use Selpol\Task\Tasks\Intercom\Key\IntercomKeysKeyTask;
use Throwable;

readonly class house extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        $flats = $households->getFlats("houseId", $params["_id"], true);

        if ($flats) {
            usort($flats, static fn(array $a, array $b): int => $a['flat'] > $b['flat'] ? 1 : -1);
        }

        $house = ["flats" => $flats];

        $service = container(AuthService::class);

        if ($service->checkScope('houses-entrance-get')) {
            $house['entrances'] = $households->getEntrances("houseId", $params["_id"]);
        }

        if ($service->checkScope('cameras-cameras-get')) {
            $house['cameras'] = $households->getCameras("houseId", $params["_id"]);
        }

        if ($service->checkScope('intercom-model-get')) {
            $house['domophoneModels'] = IntercomModel::modelsToArray();
        }

        if ($service->checkScope('houses-cms-get')) {
            $house['cmses'] = IntercomCms::modelsToArray();
        }

        if ($house['flats']) {
            return self::success($house);
        }

        return self::error('Не удалось найти дом', 404);
    }

    public static function POST(array $params): ResponseInterface
    {
        $houseId = $params['_id'];
        $keys = $params['keys'];

        foreach ($keys as $key) {
            try {
                $houseKey = new HouseKey();

                $houseKey->rfid = $key['rfId'];
                $houseKey->access_type = 2;
                $houseKey->access_to = $key['accessTo'];
                $houseKey->comments = array_key_exists('comment', $key) ? $key['comment'] : '';

                $houseKey->insert();
            } catch (Throwable) {

            }
        }

        $task = task(new IntercomKeysKeyTask($houseId, $keys));

        if (count($keys) < 25) {
            $task->sync();
        } else {
            $task->high()->dispatch();
        }

        return self::success();
    }

    public static function index(): bool|array
    {
        return [
            "GET" => "[Дом] Получить дом",
            "POST" => "[Дом] Загрузить ключи"
        ];
    }
}