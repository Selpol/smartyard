<?php

/**
 * addresses api
 */

namespace api\addresses {

    use api\api;
    use Selpol\Feature\Address\AddressFeature;

    /**
     * house method
     */
    class house extends api
    {

        public static function GET($params)
        {
            $house = container(AddressFeature::class)->getHouse($params["_id"]);

            return api::ANSWER($house, ($house !== false) ? "house" : "notAcceptable");
        }

        public static function PUT($params)
        {
            $success = container(AddressFeature::class)->modifyHouse($params["_id"], $params["settlementId"], $params["streetId"], $params["houseUuid"], $params["houseType"], $params["houseTypeFull"], $params["houseFull"], $params["house"]);

            return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
        }

        public static function POST($params)
        {
            if (@$params["magic"]) {
                $houseId = container(AddressFeature::class)->addHouseByMagic($params["magic"]);
            } else {
                $houseId = container(AddressFeature::class)->addHouse($params["settlementId"], $params["streetId"], $params["houseUuid"], $params["houseType"], $params["houseTypeFull"], $params["houseFull"], $params["house"]);
            }

            return api::ANSWER($houseId, ($houseId !== false) ? "houseId" : false);
        }

        public static function DELETE($params)
        {
            $success = container(AddressFeature::class)->deleteHouse($params["_id"]);

            return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
        }

        public static function index()
        {
            return ["GET", "PUT", "POST", "DELETE"];
        }
    }
}