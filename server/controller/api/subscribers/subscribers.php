<?php

/**
 * subscribers api
 */

namespace api\subscribers {

    use api\api;
    use Selpol\Feature\House\HouseFeature;

    /**
     * subscribers method
     */
    class subscribers extends api
    {
        public static function GET($params)
        {
            $households = container(HouseFeature::class);

            $flat = [
                "subscribers" => $households->getSubscribers(@$params["by"], @$params["query"]),
                "cameras" => $households->getCameras(@$params["by"], @$params["query"]),
                "keys" => $households->getKeys(@$params["by"], @$params["query"]),
            ];

            return api::ANSWER($flat, $flat ? "flat" : false);
        }

        public static function index()
        {
            return [
                "GET" => "[Абоненты] Получить список",
            ];
        }
    }
}