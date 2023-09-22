<?php

/**
 * subscribers api
 */

namespace api\subscribers {

    use api\api;
    use Selpol\Feature\House\HouseFeature;

    /**
     * suvscriberCameras method
     */
    class subscriberCameras extends api
    {

        public static function POST($params)
        {
            $households = container(HouseFeature::class);

            $cameraId = $households->addCamera("subscriber", $params["subscriberId"], $params["cameraId"]);

            return api::ANSWER($cameraId, ($cameraId !== false) ? "cameraId" : "notAcceptable");
        }

        public static function DELETE($params)
        {
            $households = container(HouseFeature::class);

            $success = $households->unlinkCamera("subscriber", $params["subscriberId"], $params["cameraId"]);

            return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
        }

        public static function index()
        {
            return [
                "POST" => "#same(addresses,house,POST)",
                "DELETE" => "#same(addresses,house,DELETE)",
            ];
        }
    }
}