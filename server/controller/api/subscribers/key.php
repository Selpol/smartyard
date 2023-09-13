<?php

/**
 * subscribers api
 */

namespace api\subscribers {

    use api\api;
    use Selpol\Task\Tasks\Intercom\IntercomKeyTask;

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

            task(new IntercomKeyTask($params['rfId'], $params['accessTo'], false))->high()->dispatch();

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

            if ($key)
                task(new IntercomKeyTask($key['rfId'], $key['accessTo'], true))->high()->dispatch();

            $success = $households->deleteKey($params["_id"]);

            return api::ANSWER($success);
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
