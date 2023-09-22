<?php

/**
 * subscribers api
 */

namespace api\subscribers {

    use api\api;
    use Selpol\Feature\House\HouseFeature;

    /**
     * flatCameras method
     */
    class flatCameras extends api
    {

        public static function POST($params)
        {
            $households = container(HouseFeature::class);

            $cameraId = $households->addCamera("flat", $params["flatId"], $params["cameraId"]);

            return api::ANSWER($cameraId, ($cameraId !== false) ? "cameraId" : "notAcceptable");
        }

        public static function DELETE($params)
        {
            $households = container(HouseFeature::class);

            $success = $households->unlinkCamera("flat", $params["flatId"], $params["cameraId"]);

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