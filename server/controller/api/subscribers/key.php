<?php

/**
 * subscribers api
 */

namespace api\subscribers {

    use api\api;
    use Selpol\Feature\House\HouseFeature;
    use Selpol\Task\Tasks\Intercom\Key\IntercomAddKeyTask;
    use Selpol\Task\Tasks\Intercom\Key\IntercomDeleteKeyTask;

    /**
     * key method
     */
    class key extends api
    {
        public static function GET($params)
        {
            $key = container(HouseFeature::class)->getKey($params['_id']);

            return api::ANSWER($key, ($key !== false) ? 'key' : false);
        }

        public static function POST($params)
        {
            $households = container(HouseFeature::class);

            $keyId = $households->addKey($params["rfId"], $params["accessType"], $params["accessTo"], $params["comments"]);

            task(new IntercomAddKeyTask($params['rfId'], $params['accessTo']))->high()->dispatch();

            return api::ANSWER($keyId, ($keyId !== false) ? "key" : false);
        }

        public static function PUT($params)
        {
            $households = container(HouseFeature::class);

            $success = $households->modifyKey($params["_id"], $params["comments"]);

            return api::ANSWER($success);
        }

        public static function DELETE($params)
        {
            $households = container(HouseFeature::class);

            $key = $households->getKey($params['_id']);

            if ($key) {
                $success = $households->deleteKey($params["_id"]);

                if ($success)
                    task(new IntercomDeleteKeyTask($key['rfId'], $key['accessTo']))->high()->dispatch();

                return api::ANSWER($success);
            }

            return api::ERROR('Ключ не найден');
        }

        public static function index()
        {
            return [
                "GET" => "#same(addresses,house,GET)",
                "PUT" => "#same(addresses,house,PUT)",
                "POST" => "#same(addresses,house,POST)",
                "DELETE" => "#same(addresses,house,DELETE)",
            ];
        }
    }
}
