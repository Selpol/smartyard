<?php

/**
 * houses api
 */

namespace api\houses {

    use api\api;
    use Selpol\Feature\House\HouseFeature;
    use Selpol\Task\Tasks\Intercom\IntercomLockTask;

    /**
     * entrance method
     */
    class entrance extends api
    {
        public static function GET($params)
        {
            $entranceId = $params['_id'];

            $entrance = container(HouseFeature::class)->getEntrance($entranceId);

            if ($entrance)
                return api::ANSWER($entrance, 'entrance');

            return api::ERROR('Вход не найден');
        }

        public static function POST($params)
        {
            $households = container(HouseFeature::class);

            if (@$params["entranceId"]) {
                $success = $households->addEntrance($params["houseId"], $params["entranceId"], $params["prefix"]);

                return api::ANSWER($success);
            } else {
                $entranceId = $households->createEntrance($params["houseId"], $params["entranceType"], $params["entrance"], $params["lat"], $params["lon"], $params["shared"], $params["plog"], $params["prefix"], $params["callerId"], $params["domophoneId"], $params["domophoneOutput"], $params["cms"], $params["cmsType"], $params["cameraId"], $params["locksDisabled"], $params["cmsLevels"]);

                return api::ANSWER($entranceId, ($entranceId !== false) ? "entranceId" : false);
            }
        }

        public static function PUT($params)
        {
            $households = container(HouseFeature::class);

            $success = $households->modifyEntrance((int)$params["_id"], (int)$params["houseId"], $params["entranceType"], $params["entrance"], $params["lat"], $params["lon"], $params["shared"], $params["plog"], (int)$params["prefix"], $params["callerId"], $params["domophoneId"], $params["domophoneOutput"], $params["cms"], $params["cmsType"], $params["cameraId"], $params["locksDisabled"], $params["cmsLevels"]);

            if ($success)
                task(new IntercomLockTask((int)$params["_id"], (bool)$params["locksDisabled"] ?? false))->high()->dispatch();

            return api::ANSWER($success);
        }

        public static function DELETE($params)
        {
            $households = container(HouseFeature::class);

            if (@$params["houseId"]) {
                $success = $households->deleteEntrance($params["_id"], $params["houseId"]);
            } else {
                $success = $households->destroyEntrance($params["_id"]);
            }

            return api::ANSWER($success);
        }

        public static function index()
        {
            return [
                "GET" => "#same(addresses,house,GET)",
                "POST" => "#same(addresses,house,PUT)",
                "PUT" => "#same(addresses,house,PUT)",
                "DELETE" => "#same(addresses,house,PUT)",
            ];
        }
    }
}