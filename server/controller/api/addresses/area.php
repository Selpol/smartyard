<?php

/**
 * addresses api
 */

namespace api\addresses {

    use api\api;
    use Selpol\Feature\Address\AddressFeature;

    /**
     * area method
     */
    class area extends api
    {
        public static function PUT($params)
        {
            $success = container(AddressFeature::class)->modifyArea($params["_id"], $params["regionId"], $params["areaUuid"], $params["areaWithType"], $params["areaType"], $params["areaTypeFull"], $params["area"], $params["timezone"]);

            return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
        }

        public static function POST($params)
        {
            $areaId = container(AddressFeature::class)->addArea($params["regionId"], $params["areaUuid"], $params["areaWithType"], $params["areaType"], $params["areaTypeFull"], $params["area"], $params["timezone"]);

            return api::ANSWER($areaId, ($areaId !== false) ? "areaId" : "notAcceptable");
        }

        public static function DELETE($params)
        {
            $success = container(AddressFeature::class)->deleteArea($params["_id"]);

            return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
        }

        public static function index()
        {
            return [
                "PUT" => "[Адрес] Обновить область",
                "POST" => "[Адрес] Создать область",
                "DELETE" => "[Адрес] Удалить область",
            ];
        }
    }
}