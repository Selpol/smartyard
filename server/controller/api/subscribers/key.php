<?php

/**
 * subscribers api
 */

namespace api\subscribers {

    use api\api;
    use Selpol\Task\Tasks\Intercom\IntercomAddKeyTask;
    use Selpol\Task\Tasks\Intercom\IntercomDeleteKeyTask;

    /**
     * key method
     */
    class key extends api
    {
        public static function POST($params)
        {
            $households = backend("households");

            $keyId = $households->addKey($params["rfId"], $params["accessType"], $params["accessTo"], $params["comments"]);

            task(new IntercomAddKeyTask($params['rfId'], $params['accessTo']))->high()->dispatch();

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
                task(new IntercomDeleteKeyTask($key['rfId'], $key['accessTo']))->high()->dispatch();

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
