<?php

namespace Selpol\Controller\api\houses {

    use api\api;
    use Selpol\Task\Tasks\Intercom\Key\IntercomHouseKeyTask;

    class key extends api
    {
        public static function POST($params)
        {
            $households = backend("households");

            $houseId = $params['_id'];
            $keys = $params['keys'];

            foreach ($keys as $key) {
                $households->addKey($key["rfId"], 2, $key["accessTo"], '');
            }

            task(new IntercomHouseKeyTask($houseId));

            return api::ANSWER();
        }

        public static function index(): array
        {
            return [
                "POST" => "#same(addresses,house,PUT)",
            ];
        }
    }
}