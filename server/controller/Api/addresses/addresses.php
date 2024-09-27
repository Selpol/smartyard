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
            $r["regions"] = $regionId !== 0 ? [$addresses->getRegion($regionId)] : $addresses->getRegions();
        }

        if (str_contains($include, "areas")) {
            $r["areas"] = $areaId !== 0 ? [$addresses->getArea($areaId)] : $addresses->getAreas($regionId);
        }

        if (str_contains($include, "cities")) {
            $r["cities"] = $cityId !== 0 ? [$addresses->getCity($cityId)] : $addresses->getCities($regionId, $areaId);
        }

        if (str_contains($include, "settlements")) {
            if ($settlementId !== 0) {
                $r["settlements"] = [$addresses->getSettlement($settlementId)];
            } else {
                $r["settlements"] = $addresses->getSettlements($areaId, $cityId);
            }
        }

        if (str_contains($include, "streets")) {
            $r["streets"] = $streetId !== 0 ? [$addresses->getStreet($streetId)] : $addresses->getStreets($cityId, $settlementId);
        }

        if (str_contains($include, "houses")) {
            $r["houses"] = $houseId !== 0 ? [$addresses->getHouse($houseId)] : $addresses->getHouses($settlementId, $streetId);
        }

        if ($r !== []) {
            return self::success($r);
        }

        return self::error('Не удалось получить адреса', 404);
    }

    public static function index(): bool|array
    {
        return ['GET' => '[Адрес] Получить список'];
    }
}