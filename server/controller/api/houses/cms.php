<?php

/**
 * houses api
 */

namespace api\houses {

    use api\api;
    use Selpol\Task\Tasks\Intercom\IntercomCmsTask;

    /**
     * house method
     */
    class cms extends api
    {
        public static function GET($params)
        {
            $households = backend("households");

            $cms = $households->getCms($params["_id"]);

            return api::ANSWER($cms, ($cms !== false) ? "cms" : false);
        }

        public static function PUT($params)
        {
            $households = backend("households");

            $success = $households->setCms($params["_id"], $params["cms"]);

            dispatch_high(new IntercomCmsTask($params['_id']));

            return api::ANSWER($success);
        }

        public static function index()
        {
            return [
                "GET" => "#same(addresses,house,GET)",
                "PUT" => "#same(addresses,house,PUT)",
            ];
        }
    }
}