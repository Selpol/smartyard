<?php

/**
 * houses api
 */

namespace api\houses {

    use api\api;
    use Selpol\Feature\House\HouseFeature;

    /**
     * entrance method
     */
    class cameras extends api
    {

        public static function POST($params)
        {
            $households = container(HouseFeature::class);

            $cameraId = $households->addCamera("house", $params["houseId"], $params["cameraId"]);

            return api::ANSWER($cameraId, ($cameraId !== false) ? "cameraId" : false);
        }

        public static function DELETE($params)
        {
            $households = container(HouseFeature::class);

            $success = $households->unlinkCamera("house", $params["houseId"], $params["cameraId"]);

            return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
        }

        public static function index()
        {
            return [
                "POST" => "#same(addresses,house,PUT)",
                "DELETE" => "#same(addresses,house,PUT)",
            ];
        }
    }
}