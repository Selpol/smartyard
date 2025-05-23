<?php

namespace Selpol\Controller\Api\houses;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\House\HouseFlat;
use Selpol\Feature\House\HouseFeature;
use Selpol\Service\AuthService;
use Selpol\Task\Tasks\Intercom\Flat\IntercomDeleteFlatTask;
use Selpol\Task\Tasks\Intercom\Flat\IntercomSyncFlatTask;

readonly class flat extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $flatId = @$params['_id'];

        if (!isset($flatId)) {
            return self::error('Идентификатор квартиры обязателен для заполнения', 400);
        }

        $flat = container(HouseFeature::class)->getFlat($flatId);

        return $flat ? self::success($flat) : self::error('Квартира не найдена', 404);
    }

    public static function POST(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        if (strlen($params['openCode']) > 1) {
            $flat = HouseFlat::fetch(criteria()->equal('address_house_id', (int)$params['houseId'])->simple('flat', '!=', $params['flat'])->equal('open_code', $params['openCode']), setting()->columns(['flat']));

            if ($flat != null) {
                return self::error('В квартире ' . $flat->flat . ' уже существует код ' . $params['openCode'], 400);
            }
        }

        $flatId = $households->addFlat((int)$params["houseId"], $params["floor"], $params["flat"], $params["code"], $params["entrances"], $params["apartmentsAndLevels"], $params["openCode"], (int)$params["plog"], (int)$params["autoOpen"], (int)$params["whiteRabbit"], (int)$params["sipEnabled"], $params["sipPassword"], $params['comment']);

        if ($flatId) {
            task(new IntercomSyncFlatTask(intval(container(AuthService::class)->getUser()->getIdentifier()), $flatId, true))->high()->async();
        }

        return $flatId ? self::success($flatId) : self::error('Не удалось создать квартиру', 400);
    }

    public static function PUT(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        if (strlen($params['openCode']) > 1) {
            $flat = HouseFlat::fetch(criteria()->equal('address_house_id', (int)$params['houseId'])->simple('flat', '!=', $params['flat'])->equal('open_code', $params['openCode']), setting()->columns(['flat']));

            if ($flat != null) {
                return self::error('В квартире ' . $flat->flat . ' уже существует код ' . $params['openCode'], 400);
            }
        }

        $success = $households->modifyFlat($params["_id"], $params);

        if ($success) {
            task(new IntercomSyncFlatTask(intval(container(AuthService::class)->getUser()->getIdentifier()), $params['_id'], false))->high()->async();
        }

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
                $flatEntrances = array_reduce($flat['entrances'], static function (array $previous, array $current) use ($flat, $entrances): array {
                    $currentEntrances = array_filter($entrances, static fn(array $entrance): bool => $entrance['entranceId'] == $current['entranceId']);

                    if ($currentEntrances !== []) {
                        $previous[] = [$current['apartment'], $current['entranceId']];
                    }

                    return $previous;
                }, []);

                task(new IntercomDeleteFlatTask($flat['flatId'], $flatEntrances))->high()->async();
            }

            return $success ? self::success() : self::error('Не удалось удалить квартиру', 400);
        }

        return self::error('Квартиры не найдена', 404);
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Deprecated] [Дом] Получить квартиру', 'POST' => '[Deprecated] [Дом] Создать квартиру', 'PUT' => '[Deprecated] [Дом] Обновить квартиру', 'DELETE' => '[Deprecated] [Дом] Удалить квартиру'];
    }
}