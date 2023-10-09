<?php

/**
 * subscribers api
 */

namespace api\subscribers {

    use api\api;
    use Selpol\Feature\House\HouseFeature;

    /**
     * keys method
     */
    class keys extends api
    {

        public static function GET($params)
        {
            $households = container(HouseFeature::class);
            $keys = $households->getKeys('flatId', $params['_id']);

            return api::ANSWER($keys, ($keys !== false) ? "keys" : false);
        }

        public static function index()
        {
            return [
                "GET" => "#same(addresses,house,GET)",
            ];
        }
    }
}