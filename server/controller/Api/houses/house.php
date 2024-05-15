<?php

namespace Selpol\Controller\Api\houses;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Device\Ip\Intercom\IntercomCms;
use Selpol\Device\Ip\Intercom\IntercomModel;
use Selpol\Entity\Model\House\HouseKey;
use Selpol\Feature\House\HouseFeature;
use Selpol\Service\DatabaseService;
use Selpol\Task\Tasks\Intercom\Key\IntercomKeysKeyTask;
use Throwable;

readonly class house extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        $flats = $households->getFlats("houseId", $params["_id"], true);

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

        if ($house)
            return self::success($house);

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