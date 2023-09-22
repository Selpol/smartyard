<?php

/**
 * addresses api
 */

namespace api\addresses {

    use api\api;
    use Selpol\Feature\Address\AddressFeature;

    /**
     * addresses method
     */
    class addresses extends api
    {
        public static function GET($params)
        {
            $addresses = container(AddressFeature::class);

            $regionId = @(int)$params["regionId"];
            $areaId = @(int)$params["areaId"];
            $cityId = @(int)$params["cityId"];
            $settlementId = @(int)$params["settlementId"];
            $streetId = @(int)$params["streetId"];
            $houseId = @(int)$params["houseId"];

            $include = @$params["include"] ?: "regions,areas,cities,settlements,streets,houses";

            $r = [];

            if (str_contains($include, "regions")) {
                $r["regions"] = $addresses->getRegions();
            }

            if (str_contains($include, "areas")) {
                if ($areaId) {
                    $r["areas"] = [$addresses->getArea($areaId)];
                } else {
                    $r["areas"] = $addresses->getAreas($regionId);
                }
            }

            if (str_contains($include, "cities")) {
                if ($cityId) {
                    $r["cities"] = [$addresses->getCity($cityId)];
                } else {
                    $r["cities"] = $addresses->getCities($regionId, $areaId);
                }
            }

            if (str_contains($include, "settlements")) {
                if ($settlementId) {
                    $r["settlements"] = [$addresses->getSettlement($settlementId)];
                } else {
                    $r["settlements"] = $addresses->getSettlements($areaId, $cityId);
                }
            }

            if (str_contains($include, "streets")) {
                if ($streetId) {
                    $r["streets"] = [$addresses->getStreet($streetId)];
                } else {
                    $r["streets"] = $addresses->getStreets($cityId, $settlementId);
                }
            }

            if (str_contains($include, "houses")) {
                if ($houseId) {
                    $r["houses"] = [$addresses->getHouse($houseId)];
                } else {
                    $r["houses"] = $addresses->getHouses($settlementId, $streetId);
                }
            }

            return api::ANSWER($r, ($r !== false) ? "addresses" : "badRequest");
        }

        public static function index()
        {
            return ["GET" => "#common"];
        }
    }
}