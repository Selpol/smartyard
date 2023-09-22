<?php

/**
 * cameras api
 */

namespace api\cameras {

    use api\api;
    use Selpol\Device\Ip\Camera\CameraModel;
    use Selpol\Feature\Camera\CameraFeature;
    use Selpol\Feature\Frs\FrsFeature;

    /**
     * cameras method
     */
    class cameras extends api
    {

        public static function GET($params)
        {
            $response = [
                "cameras" => container(CameraFeature::class)->getCameras(),
                "models" => CameraModel::modelsToArray(),
                "frsServers" => container(FrsFeature::class)->servers(),
            ];

            return api::ANSWER($response, "cameras");
        }

        public static function index()
        {
            return [
                "GET" => "#same(addresses,house,GET)",
            ];
        }
    }
}