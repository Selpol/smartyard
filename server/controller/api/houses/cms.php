<?php

/**
 * houses api
 */

namespace api\houses {

    use api\api;
    use Selpol\Feature\House\HouseFeature;
    use Selpol\Task\Tasks\Intercom\IntercomCmsTask;

    /**
     * house method
     */
    class cms extends api
    {
        public static function GET($params)
        {
            $households = container(HouseFeature::class);

            $cms = $households->getCms($params["_id"]);

            return api::ANSWER($cms, ($cms !== false) ? "cms" : false);
        }

        public static function PUT($params)
        {
            $households = container(HouseFeature::class);

            $success = $households->setCms($params["_id"], $params["cms"]);

            if ($success)
                task(new IntercomCmsTask($params['_id']))->sync();

            return api::ANSWER($success);
        }

        public static function index(): array
        {
            return [
                "GET" => "[Дом] Получить КМС",
                "PUT" => "[Дом] Обновить КМС",
            ];
        }
    }
}