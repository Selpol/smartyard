<?php

/**
 * houses api
 */

namespace api\houses {

    use api\api;
    use Selpol\Task\Tasks\Intercom\IntercomConfigureTask;

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

            $domophones = $households->getDomophones('house', $params['_id']);

            if ($domophones && count($domophones) > 0) {
                $id = $domophones['domophoneId'];

                task(new IntercomConfigureTask($id, IntercomConfigureTask::SYNC_CMS))->high()->dispatch();
            }

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