<?php

namespace Selpol\Controller\Api\houses;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Device\Ip\Intercom\IntercomCms;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Feature\House\HouseFeature;
use Selpol\Task\Tasks\Intercom\Key\IntercomKeysKeyTask;

readonly class house extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        $flats = $households->getFlats("houseId", $params["_id"]);

        if ($flats)
            usort($flats, static fn(array $a, array $b) => $a['flat'] > $b['flat'] ? 1 : -1);

        $house = [
            "flats" => $flats,
            "entrances" => $households->getEntrances("houseId", $params["_id"]),
            "cameras" => $households->getCameras("houseId", $params["_id"]),
            "domophoneModels" => IntercomModel::modelsToArray(),
            "cmses" => IntercomCms::modelsToArray(),
        ];

        $house = ($house["flats"] !== false && $house["entrances"] !== false && $house["domophoneModels"] !== false && $house["cmses"] !== false) ? $house : false;

        return self::success($house);
    }

    public static function POST(array $params): ResponseInterface
    {
        $houseId = $params['_id'];
        $keys = $params['keys'];

        foreach ($keys as $key) {
            $houseKey = new HouseKey();

            $houseKey->rfid = $key['rfId'];
            $houseKey->access_type = 2;
            $houseKey->access_to = $key['accessTo'];
            $houseKey->comments = array_key_exists('comment', $key) ? $key['comment'] : '';

            $houseKey->insert();
        }

        $task = task(new IntercomKeysKeyTask($houseId, $keys));

        if (count($keys) < 25) $task->sync();
        else $task->high()->dispatch();

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