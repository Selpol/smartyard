<?php

/**
 * subscribers api
 */

namespace api\subscribers {

    use api\api;
    use Selpol\Task\Tasks\Intercom\Key\IntercomAddKeyTask;
    use Selpol\Task\Tasks\Intercom\Key\IntercomDeleteKeyTask;

    /**
     * key method
     */
    class key extends api
    {
        public static function GET($params)
        {
            $key = backend('households')->getKey($params['_id']);

            return api::ANSWER($key, ($key !== false) ? 'key' : false);
        }

        public static function POST($params)
        {
            $households = backend("households");

            $keyId = $households->addKey($params["rfId"], $params["accessType"], $params["accessTo"], $params["comments"]);

            dispatch_high(new IntercomAddKeyTask($params['rfId'], $params['accessTo']));

            return api::ANSWER($keyId, ($keyId !== false) ? "key" : false);
        }

        public static function PUT($params)
        {
            $households = backend("households");

            $success = $households->modifyKey($params["_id"], $params["comments"]);

            return api::ANSWER($success);
        }

        public static function DELETE($params)
        {
            $households = backend("households");

            $key = $households->getKey($params['_id']);

            if ($key) {
                $success = $households->deleteKey($params["_id"]);

                if ($success)
                    dispatch_high(new IntercomDeleteKeyTask($key['rfId'], $key['accessTo']));

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
