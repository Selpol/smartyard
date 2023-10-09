<?php

/**
 * addresses api
 */

namespace api\addresses {

    use api\api;
    use Selpol\Feature\Address\AddressFeature;

    /**
     * region method
     */
    class region extends api
    {

        public static function PUT($params)
        {
            $success = container(AddressFeature::class)->modifyRegion($params["_id"], $params["regionUuid"], $params["regionIsoCode"], $params["regionWithType"], $params["regionType"], $params["regionTypeFull"], $params["region"], $params["timezone"]);

            return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
        }

        public static function POST($params)
        {
            $regionId = container(AddressFeature::class)->addRegion($params["regionUuid"], $params["regionIsoCode"], $params["regionWithType"], $params["regionType"], $params["regionTypeFull"], $params["region"], $params["timezone"]);

            return api::ANSWER($regionId, ($regionId !== false) ? "regionId" : "notAcceptable");
        }

        public static function DELETE($params)
        {
            $success = container(AddressFeature::class)->deleteRegion($params["_id"]);

            return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
        }

        public static function index(): array
        {
            return [
                "PUT" => "[Регион] Обновить регион",
                "POST" => "[Регион] Создать регион",
                "DELETE" => "[Регион] Удалить регион",
            ];
        }
    }
}