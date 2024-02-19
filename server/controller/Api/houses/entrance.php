<?php

namespace Selpol\Controller\Api\houses;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Entity\Model\House\HouseEntrance;
use Selpol\Feature\House\HouseFeature;
use Selpol\Task\Tasks\Intercom\Cms\IntercomSetCmsTask;
use Selpol\Task\Tasks\Intercom\IntercomLevelTask;
use Selpol\Task\Tasks\Intercom\IntercomUnlockTask;

readonly class entrance extends Api
{
    public static function GET(array $params): ResponseInterface
    {
        $entranceId = intval($params['_id']);

        $entrance = container(HouseFeature::class)->getEntranceWithPrefix($entranceId, array_key_exists('prefix', $params) ? $params['prefix'] : 0);

        return $entrance ? self::success($entrance) : self::error('Вход не найден', 404);
    }

    public static function POST(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        if (@$params["entranceId"]) {
            $success = $households->addEntrance($params["houseId"], $params["entranceId"], $params["prefix"]);

            return $success ? self::success($params['entranceId']) : self::error('Не удалось добавить общий вход', 400);
        } else {
            $entranceId = $households->createEntrance($params["houseId"], $params["entranceType"], $params["entrance"], $params["lat"], $params["lon"], $params["shared"], $params["plog"], $params["prefix"], $params["callerId"], $params["domophoneId"], $params["domophoneOutput"], $params["cms"], $params["cmsType"], $params["cameraId"], $params["locksDisabled"], $params["cmsLevels"]);

            if ($entranceId) {
                task(new IntercomUnlockTask(intval($params['domophoneId']), boolval($params['locksDisabled'])))->high()->dispatch();
                task(new IntercomSetCmsTask(intval($params['domophoneId']), $params['cms']))->high()->dispatch();
            }

            return $entranceId ? self::success($entranceId) : self::error('Не удалось создать вход', 400);
        }
    }

    public static function PUT(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        $entrance = HouseEntrance::findById(intval($params['_id']), setting: setting()->nonNullable());

        $success = $households->modifyEntrance((int)$params["_id"], (int)$params["houseId"], $params["entranceType"], $params["entrance"], $params["lat"], $params["lon"], $params["shared"], $params["plog"], (int)$params["prefix"], $params["callerId"], $params["domophoneId"], $params["domophoneOutput"], $params["cms"], $params["cmsType"], $params["cameraId"], $params["locksDisabled"], $params["cmsLevels"]);

        if ($success) {
            task(new IntercomUnlockTask(intval($params['domophoneId']), boolval($params['locksDisabled'])))->high()->dispatch();

            if ($entrance->cms !== $params['cms'])
                task(new IntercomSetCmsTask(intval($params['domophoneId']), $params['cms']))->high()->dispatch();

            if ($entrance->cms_levels !== $params['cmsLevels'])
                task(new IntercomLevelTask($entrance->house_domophone_id))->high()->dispatch();
        }

        return $success ? self::success($params['_id']) : self::error('Не удалось обновить вход', 400);
    }

    public static function DELETE(array $params): ResponseInterface
    {
        $households = container(HouseFeature::class);

        if (@$params["houseId"])
            $success = $households->deleteEntrance($params["_id"], $params["houseId"]);
        else
            $success = $households->destroyEntrance($params["_id"]);

        return $success ? self::success() : self::error(@$params["houseId"] ? 'Не удалось отвязать вход от дома' : 'Не удалось удалить вход', 400);
    }

    public static function index(): array
    {
        return ['GET' => '[Дом] Получить вход', 'POST' => '[Дом] Создать вход', 'PUT' => '[Дом] Обновить вход', 'DELETE' => '[Дом] Удалить вход'];
    }
}