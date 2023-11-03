<?php

namespace Selpol\Controller\Api\houses;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\House\HouseFeature;
use Selpol\Task\Tasks\Intercom\Flat\IntercomDeleteFlatTask;
use Selpol\Task\Tasks\Intercom\Flat\IntercomSyncFlatTask;

readonly class flat extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $flatId = @$params['_id'];

        if (!isset($flatId))
            return self::error('Идентификатор квартиры обязателен для заполнения', 400);

        $flat = container(HouseFeature::class)->getFlat($flatId);

        return $flat ? self::success($flat) : self::error('Квартира не найдена', 404);
    }

    public static function POST(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        $flatId = $households->addFlat((int)$params["houseId"], $params["floor"], $params["flat"], $params["code"], $params["entrances"], $params["apartmentsAndLevels"], (int)$params["manualBlock"], (int)$params["adminBlock"], $params["openCode"], (int)$params["plog"], (int)$params["autoOpen"], (int)$params["whiteRabbit"], (int)$params["sipEnabled"], $params["sipPassword"]);

        if ($flatId)
            task(new IntercomSyncFlatTask($flatId, true))->high()->dispatch();

        return $flatId ? self::success($flatId) : self::error('Не удалось создать квартиру', 400);
    }

    public static function PUT(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        $success = $households->modifyFlat($params["_id"], $params);

        if ($success)
            task(new IntercomSyncFlatTask($params['_id'], false))->high()->dispatch();

        return $success ? self::success($params['_id']) : self::error('Не удалось обновить квартиру', 400);
    }

    public static function DELETE(array $params): ResponseInterface
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
                        $previous[] = [$flat['flat'] !== $current['apartment'] ? $current['apartment'] : $flat['flat'], $current['entranceId']];

                    return $previous;
                }, []);

                task(new IntercomDeleteFlatTask($flat['flatId'], $flatEntrances))->high()->dispatch();
            }

            return $success ? self::success() : self::error('Не удалось удалить квартиру', 400);
        }

        return self::error('Квартиры не найдена', 404);
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Дом] Получить квартиру', 'POST' => '[Дом] Создать квартиру', 'PUT' => '[Дом] Обновить квартиру', 'DELETE' => '[Дом] Удалить квартиру'];
    }
}