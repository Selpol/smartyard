<?php

/**
 * addresses api
 */

namespace api\addresses {

    use api\api;
    use Selpol\Feature\Address\AddressFeature;

    /**
     * settlement method
     */
    class settlement extends api
    {

        public static function PUT($params)
        {
            $success = container(AddressFeature::class)->modifySettlement($params["_id"], $params["areaId"], $params["cityId"], $params["settlementUuid"], $params["settlementWithType"], $params["settlementType"], $params["settlementTypeFull"], $params["settlement"]);

            return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
        }

        public static function POST($params)
        {
            $settlementId = container(AddressFeature::class)->addSettlement($params["areaId"], $params["cityId"], $params["settlementUuid"], $params["settlementWithType"], $params["settlementType"], $params["settlementTypeFull"], $params["settlement"]);

            return api::ANSWER($settlementId, ($settlementId !== false) ? "settlementId" : "notAcceptable");
        }

        public static function DELETE($params)
        {
            $success = container(AddressFeature::class)->deleteSettlement($params["_id"]);

            return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
        }

        public static function index(): array
        {
            return [
                "PUT" => "[Поселение] Обновить поселение",
                "POST" => "[Поселение] Создать поселение",
                "DELETE" => "[Поселение] Удалить поселение",
            ];
        }
    }
}