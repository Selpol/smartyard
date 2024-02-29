<?php

namespace Selpol\Controller\Api\addresses;

use Psr\Http\Message\ResponseInterface;
use Selpol\Controller\Api\Api;
use Selpol\Feature\Address\AddressFeature;

readonly class addresses extends Api
{
    public static function GET(array $params): ResponseInterface
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
            if ($regionId) {
                $r["regions"] = [$addresses->getRegion($regionId)];
            } else {
                $r["regions"] = $addresses->getRegions();
            }
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

        if (count($r))
            return self::success($r);

        return self::error('Не удалось получить адреса', 404);
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Адрес] Получить список'];
    }
}