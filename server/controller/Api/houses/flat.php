<?php

namespace Selpol\Controller\Api\houses;

use Selpol\Controller\Api\api;
use Selpol\Feature\House\HouseFeature;
use Selpol\Task\Tasks\Intercom\Flat\IntercomDeleteFlatTask;
use Selpol\Task\Tasks\Intercom\Flat\IntercomSyncFlatTask;

class flat extends api
{
    public static function GET(array $params): array
    {
        $flatId = @$params['_id'];

        if (!isset($flatId))
            return api::ERROR('Неверный формат данных');

        $flat = container(HouseFeature::class)->getFlat($flatId);

        return api::ANSWER($flat, ($flat !== false) ? 'flat' : 'notAcceptable');
    }

    public static function POST(array $params): array
    {
        $households = container(HouseFeature::class);

        $flatId = $households->addFlat((int)$params["houseId"], $params["floor"], $params["flat"], $params["code"], $params["entrances"], $params["apartmentsAndLevels"], (int)$params["manualBlock"], (int)$params["adminBlock"], $params["openCode"], (int)$params["plog"], (int)$params["autoOpen"], (int)$params["whiteRabbit"], (int)$params["sipEnabled"], $params["sipPassword"]);

        if ($flatId)
            task(new IntercomSyncFlatTask($flatId, true))->sync();

        return api::ANSWER($flatId, ($flatId !== false) ? "flatId" : "notAcceptable");
    }

    public static function PUT(array $params): array
    {
        $households = container(HouseFeature::class);

        $success = $households->modifyFlat($params["_id"], $params);

        if ($success)
            task(new IntercomSyncFlatTask($params['_id'], false))->sync();

        return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
    }

    public static function DELETE(array $params): array
    {
        $households = container(HouseFeature::class);

        $flat = $households->getFlat($params['_id']);

        if ($flat) {
            $entrances = $households->getEntrances('flatId', $flat['flatId']);

            $success = $households->deleteFlat($params["_id"]);

            if ($success) {
                $flatEntrances = array_reduce($flat['entrances'], static function (array $previous, array $current) use ($flat, $entrances) {
                    $currentEntrances = array_filter($entrances, static fn(array $entrance) => $entrance['entranceId'] == $current['entranceId']);

                    if (count($currentEntrances) > 0)
                        $previous[] = [
                            $flat['flat'] !== $current['apartment'] ? $current['apartment'] : $flat['flat'],
                            $current['entranceId']
                        ];

                    return $previous;
                }, []);

                task(new IntercomDeleteFlatTask($flat['flatId'], $flatEntrances))->sync();
            }

            return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
        }

        return api::ERROR('Дом не найден');
    }

    public static function index(): bool|array
    {
        return [
            'GET' => '[Дом] Получить квартиру',
            "POST" => "[Дом] Создать квартиру",
            "PUT" => "[Дом] Обновить квартиру",
            "DELETE" => "[Дом] Удалить квартиру",
        ];
    }
}