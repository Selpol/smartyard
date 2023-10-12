<?php

namespace Selpol\Feature\Address\Internal;

use Psr\Container\NotFoundExceptionInterface;
use RedisException;
use Selpol\Feature\Address\AddressFeature;

class InternalAddressFeature extends AddressFeature
{
    function getRegions(): bool|array
    {
        return $this->getDatabase()->get(
            "select address_region_id, region_uuid, region_iso_code, region_with_type, region_type, region_type_full, region, timezone from addresses_regions order by region",
            map: [
                "address_region_id" => "regionId",
                "region_uuid" => "regionUuid",
                "region_iso_code" => "regionIsoCode",
                "region_with_type" => "regionWithType",
                "region_type" => "regionType",
                "region_type_full" => "regionTypeFull",
                "region" => "region",
                "timezone" => "timezone",
            ]
        );
    }

    function getRegion(int $regionId): bool|array
    {
        return $this->getDatabase()->get(
            "select address_region_id, region_uuid, region_iso_code, region_with_type, region_type, region_type_full, region, timezone from addresses_regions where address_region_id = :address_region_id",
            [":address_region_id" => $regionId,],
            [
                "address_region_id" => "regionId",
                "region_uuid" => "regionUuid",
                "region_iso_code" => "regionIsoCode",
                "region_with_type" => "regionWithType",
                "region_type" => "regionType",
                "region_type_full" => "regionTypeFull",
                "region" => "region",
                "timezone" => "timezone",
            ],
            ["singlify"]
        );
    }

    function modifyRegion(int $regionId, string $regionUuid, string $regionIsoCode, string $regionWithType, string $regionType, string $regionTypeFull, string $region, ?string $timezone = "-"): bool
    {
        if ($timezone == "-")
            $timezone = null;

        if ($regionId && trim($regionWithType) && trim($region)) {
            return $this->getDatabase()->modify(
                "update addresses_regions set region_uuid = :region_uuid, region_iso_code = :region_iso_code, region_with_type = :region_with_type, region_type = :region_type, region_type_full = :region_type_full, region = :region, timezone = :timezone where address_region_id = $regionId",
                [
                    ":region_uuid" => $regionUuid,
                    ":region_iso_code" => $regionIsoCode,
                    ":region_with_type" => $regionWithType,
                    ":region_type" => $regionType,
                    ":region_type_full" => $regionTypeFull,
                    ":region" => $region,
                    ":timezone" => $timezone,
                ]
            );
        } else return false;
    }

    function addRegion(string $regionUuid, string $regionIsoCode, string $regionWithType, string $regionType, string $regionTypeFull, string $region, ?string $timezone = "-"): bool|int
    {
        if ($timezone == "-")
            $timezone = null;

        if (trim($regionWithType) && trim($region)) {
            return $this->getDatabase()->insert(
                "insert into addresses_regions (region_uuid, region_iso_code, region_with_type, region_type, region_type_full, region, timezone) values (:region_uuid, :region_iso_code, :region_with_type, :region_type, :region_type_full, :region, :timezone)",
                [
                    ":region_uuid" => $regionUuid,
                    ":region_iso_code" => $regionIsoCode,
                    ":region_with_type" => $regionWithType,
                    ":region_type" => $regionType,
                    ":region_type_full" => $regionTypeFull,
                    ":region" => $region,
                    ":timezone" => $timezone,
                ]
            );
        } else return false;
    }

    function deleteRegion(int $regionId): bool
    {
        return $this->getDatabase()->modify("delete from addresses_regions where address_region_id = $regionId");
    }

    function getAreas(?int $regionId): bool|array
    {
        if ($regionId)
            $query = "select address_area_id, address_region_id, area_uuid, area_with_type, area_type, area_type_full, area, timezone from addresses_areas where address_region_id = $regionId order by area";
        else
            $query = "select address_area_id, address_region_id, area_uuid, area_with_type, area_type, area_type_full, area, timezone from addresses_areas order by area";

        return $this->getDatabase()->get(
            $query,
            map: [
                "address_area_id" => "areaId",
                "address_region_id" => "regionId",
                "area_uuid" => "areaUuid",
                "area_with_type" => "areaWithType",
                "area_type" => "areaType",
                "area_type_full" => "areaTypeFull",
                "area" => "area",
                "timezone" => "timezone",
            ]
        );
    }

    function getArea(int $areaId): bool|array
    {
        return $this->getDatabase()->get("select address_area_id, address_region_id, area_uuid, area_with_type, area_type, area_type_full, area, timezone from addresses_areas where address_area_id = $areaId",
            map: [
                "address_area_id" => "areaId",
                "address_region_id" => "regionId",
                "area_uuid" => "areaUuid",
                "area_with_type" => "areaWithType",
                "area_type" => "areaType",
                "area_type_full" => "areaTypeFull",
                "area" => "area",
                "timezone" => "timezone",
            ],
            options: ["singlify"]
        );
    }

    function modifyArea(int|bool|null $areaId, int|bool|null $regionId, string $areaUuid, string $areaWithType, string $areaType, string $areaTypeFull, string $area, string $timezone = "-"): bool|int
    {
        if ($timezone == "-")
            $timezone = null;

        if ($areaId && trim($areaWithType) && trim($area)) {
            return $this->getDatabase()->modify(
                "update addresses_areas set address_region_id = :address_region_id, area_uuid = :area_uuid, area_with_type = :area_with_type, area_type = :area_type, area_type_full = :area_type_full, area = :area, timezone = :timezone where address_area_id = $areaId",
                [
                    ":address_region_id" => $regionId ?: null,
                    ":area_uuid" => $areaUuid,
                    ":area_with_type" => $areaWithType,
                    ":area_type" => $areaType,
                    ":area_type_full" => $areaTypeFull,
                    ":area" => $area,
                    ":timezone" => $timezone,
                ]
            );
        } else return false;
    }

    function addArea(int $regionId, string $areaUuid, string $areaWithType, string $areaType, string $areaTypeFull, string $area, string $timezone = "-"): bool|int|string
    {
        if ($timezone == "-")
            $timezone = null;

        if (trim($areaWithType) && trim($area)) {
            return $this->getDatabase()->insert(
                "insert into addresses_areas (address_region_id, area_uuid, area_with_type, area_type, area_type_full, area, timezone) values (:address_region_id, :area_uuid, :area_with_type, :area_type, :area_type_full, :area, :timezone)",
                [
                    ":address_region_id" => $regionId ?: null,
                    ":area_uuid" => $areaUuid,
                    ":area_with_type" => $areaWithType,
                    ":area_type" => $areaType,
                    ":area_type_full" => $areaTypeFull,
                    ":area" => $area,
                    ":timezone" => $timezone,
                ]
            );
        } else return false;
    }

    function deleteArea(int $areaId): bool|int
    {
        return $this->getDatabase()->modify("delete from addresses_areas where address_area_id = $areaId");
    }

    function getCities(int|bool $regionId = false, int|bool $areaId = false): array|bool
    {
        if ($regionId && $areaId)
            return false;

        if ($regionId) {
            $query = "select address_city_id, address_region_id, address_area_id, city_uuid, city_with_type, city_type, city_type_full, city, timezone from addresses_cities where address_region_id = $regionId and coalesce(address_area_id, 0) = 0 order by city";
        } else {
            if ($areaId)
                $query = "select address_city_id, address_region_id, address_area_id, city_uuid, city_with_type, city_type, city_type_full, city, timezone from addresses_cities where address_area_id = $areaId and coalesce(address_region_id, 0) = 0 order by city";
            else
                $query = "select address_city_id, address_region_id, address_area_id, city_uuid, city_with_type, city_type, city_type_full, city, timezone from addresses_cities order by city";
        }

        return $this->getDatabase()->get(
            $query,
            map: [
                "address_city_id" => "cityId",
                "address_region_id" => "regionId",
                "address_area_id" => "areaId",
                "city_uuid" => "cityUuid",
                "city_with_type" => "cityWithType",
                "city_type" => "cityType",
                "city_type_full" => "cityTypeFull",
                "city" => "city",
                "timezone" => "timezone",
            ]
        );
    }

    function getCity(int $cityId): array|bool
    {
        return $this->getDatabase()->get(
            "select address_city_id, address_region_id, address_area_id, city_uuid, city_with_type, city_type, city_type_full, city, timezone from addresses_cities where address_city_id = $cityId",
            map: [
                "address_city_id" => "cityId",
                "address_region_id" => "regionId",
                "address_area_id" => "areaId",
                "city_uuid" => "cityUuid",
                "city_with_type" => "cityWithType",
                "city_type" => "cityType",
                "city_type_full" => "cityTypeFull",
                "city" => "city",
                "timezone" => "timezone",
            ],
            options: ['singlify']
        );
    }

    function modifyCity(int|bool|null $cityId, int|bool|null $regionId, int|bool|null $areaId, string $cityUuid, string $cityWithType, string $cityType, string $cityTypeFull, string $city, string $timezone = "-"): bool|int
    {
        if ($timezone == "-")
            $timezone = null;

        if ($regionId && $areaId)
            return false;

        if (!$regionId && !$areaId)
            return false;

        if (trim($cityWithType) && trim($city)) {
            return $this->getDatabase()->modify(
                "update addresses_cities set address_region_id = :address_region_id, address_area_id = :address_area_id, city_uuid = :city_uuid, city_with_type = :city_with_type, city_type = :city_type, city_type_full = :city_type_full, city = :city, timezone = :timezone where address_city_id = $cityId",
                [
                    ":address_region_id" => $regionId ?: null,
                    ":address_area_id" => $areaId ?: null,
                    ":city_uuid" => $cityUuid,
                    ":city_with_type" => $cityWithType,
                    ":city_type" => $cityType,
                    ":city_type_full" => $cityTypeFull,
                    ":city" => $city,
                    ":timezone" => $timezone,
                ]
            );
        } else return false;
    }

    function addCity(int $regionId, ?int $areaId, string $cityUuid, string $cityWithType, string $cityType, string $cityTypeFull, string $city, string $timezone = "-"): bool|int|string
    {
        if ($timezone == '-')
            $timezone = null;

        if ($regionId && $areaId)
            return false;

        if (!$regionId && !$areaId)
            return false;

        if (trim($cityWithType) && trim($city)) {
            return $this->getDatabase()->insert(
                "insert into addresses_cities (address_region_id, address_area_id, city_uuid, city_with_type, city_type, city_type_full, city, timezone) values (:address_region_id, :address_area_id, :city_uuid, :city_with_type, :city_type, :city_type_full, :city, :timezone)",
                [
                    ":address_region_id" => $regionId ?: null,
                    ":address_area_id" => $areaId ?: null,
                    ":city_uuid" => $cityUuid,
                    ":city_with_type" => $cityWithType,
                    ":city_type" => $cityType,
                    ":city_type_full" => $cityTypeFull,
                    ":city" => $city,
                    ":timezone" => $timezone,
                ]
            );
        } else return false;
    }

    function deleteCity(int $cityId): bool|int
    {
        return $this->getDatabase()->modify("delete from addresses_cities where address_city_id = $cityId");
    }

    function getSettlements(int|bool $areaId = false, int|bool $cityId = false): array|bool
    {
        if ($areaId && $cityId)
            return false;

        if ($areaId) {
            $query = "select address_settlement_id, address_area_id, address_city_id, settlement_uuid, settlement_with_type, settlement_type, settlement_type_full, settlement from addresses_settlements where address_area_id = $areaId and coalesce(address_city_id, 0) = 0 order by settlement";
        } else {
            if ($cityId)
                $query = "select address_settlement_id, address_area_id, address_city_id, settlement_uuid, settlement_with_type, settlement_type, settlement_type_full, settlement from addresses_settlements where address_city_id = $cityId and coalesce(address_area_id, 0) = 0 order by settlement";
            else
                $query = "select address_settlement_id, address_area_id, address_city_id, settlement_uuid, settlement_with_type, settlement_type, settlement_type_full, settlement from addresses_settlements order by settlement";
        }

        return $this->getDatabase()->get(
            $query,
            map: [
                "address_settlement_id" => "settlementId",
                "address_area_id" => "areaId",
                "address_city_id" => "cityId",
                "settlement_uuid" => "settlementUuid",
                "settlement_with_type" => "settlementWithType",
                "settlement_type" => "settlementType",
                "settlement_type_full" => "settlementTypeFull",
                "settlement" => "settlement",
            ]
        );
    }

    function getSettlement(int $settlementId): array|bool
    {
        return $this->getDatabase()->get(
            "select address_settlement_id, address_area_id, address_city_id, settlement_uuid, settlement_with_type, settlement_type, settlement_type_full, settlement from addresses_settlements where address_settlement_id = $settlementId",
            map: [
                "address_settlement_id" => "settlementId",
                "address_area_id" => "areaId",
                "address_city_id" => "cityId",
                "settlement_uuid" => "settlementUuid",
                "settlement_with_type" => "settlementWithType",
                "settlement_type" => "settlementType",
                "settlement_type_full" => "settlementTypeFull",
                "settlement" => "settlement",
            ],
            options: ["singlify"]
        );
    }

    function modifySettlement(int|bool|null $settlementId, int|bool|null $areaId, int|bool|null $cityId, string $settlementUuid, string $settlementWithType, string $settlementType, string $settlementTypeFull, string $settlement): bool|int
    {
        if ($areaId && $cityId)
            return false;

        if (!$areaId && !$cityId)
            return false;

        if (trim($settlementWithType) && trim($settlement)) {
            return $this->getDatabase()->modify("update addresses_settlements set address_area_id = :address_area_id, address_city_id = :address_city_id, settlement_uuid = :settlement_uuid, settlement_with_type = :settlement_with_type, settlement_type = :settlement_type, settlement_type_full = :settlement_type_full, settlement = :settlement where address_settlement_id = $settlementId", [
                ":address_area_id" => $areaId ?: null,
                ":address_city_id" => $cityId ?: null,
                ":settlement_uuid" => $settlementUuid,
                ":settlement_with_type" => $settlementWithType,
                ":settlement_type" => $settlementType,
                ":settlement_type_full" => $settlementTypeFull,
                ":settlement" => $settlement,
            ]);
        } else return false;
    }

    function addSettlement(int|bool|null $areaId, int|bool|null $cityId, string $settlementUuid, string $settlementWithType, string $settlementType, string $settlementTypeFull, string $settlement): bool|int|string
    {
        if ($areaId && $cityId)
            return false;

        if (!$areaId && !$cityId)
            return false;

        if (trim($settlementWithType) && trim($settlement)) {
            return $this->getDatabase()->insert("insert into addresses_settlements (address_area_id, address_city_id, settlement_uuid, settlement_with_type, settlement_type, settlement_type_full, settlement) values (:address_area_id, :address_city_id, :settlement_uuid, :settlement_with_type, :settlement_type, :settlement_type_full, :settlement)", [
                ":address_area_id" => $areaId ?: null,
                ":address_city_id" => $cityId ?: null,
                ":settlement_uuid" => $settlementUuid,
                ":settlement_with_type" => $settlementWithType,
                ":settlement_type" => $settlementType,
                ":settlement_type_full" => $settlementTypeFull,
                ":settlement" => $settlement,
            ]);
        } else return false;
    }

    function deleteSettlement(int $settlementId): bool|int
    {
        return $this->getDatabase()->modify("delete from addresses_settlements where address_settlement_id = $settlementId");
    }

    function getStreets(int|bool $cityId = false, int|bool $settlementId = false): bool|array
    {
        if ($cityId && $settlementId)
            return false;

        if ($cityId) {
            $query = "select address_street_id, address_city_id, address_settlement_id, street_uuid, street_with_type, street_type, street_type_full, street from addresses_streets where address_city_id = $cityId and coalesce(address_settlement_id, 0) = 0 order by street";
        } else {
            if ($settlementId)
                $query = "select address_street_id, address_city_id, address_settlement_id, street_uuid, street_with_type, street_type, street_type_full, street from addresses_streets where address_settlement_id = $settlementId and coalesce(address_city_id, 0) = 0 order by street";
            else
                $query = "select address_street_id, address_city_id, address_settlement_id, street_uuid, street_with_type, street_type, street_type_full, street from addresses_streets order by street";
        }

        return $this->getDatabase()->get(
            $query,
            map: [
                "address_street_id" => "streetId",
                "address_city_id" => "cityId",
                "address_settlement_id" => "settlementId",
                "street_uuid" => "streetUuid",
                "street_with_type" => "streetWithType",
                "street_type" => "streetType",
                "street_type_full" => "streetTypeFull",
                "street" => "street",
            ]
        );
    }

    function getStreet(int $streetId): bool|array
    {
        return $this->getDatabase()->get(
            "select address_street_id, address_city_id, address_settlement_id, street_uuid, street_with_type, street_type, street_type_full, street from addresses_streets where address_street_id = $streetId",
            map: [
                "address_street_id" => "streetId",
                "address_city_id" => "cityId",
                "address_settlement_id" => "settlementId",
                "street_uuid" => "streetUuid",
                "street_with_type" => "streetWithType",
                "street_type" => "streetType",
                "street_type_full" => "streetTypeFull",
                "street" => "street",
            ],
            options: ["singlify"]
        );
    }

    function modifyStreet(int $streetId, int|bool|null $cityId, int|bool|null $settlementId, string $streetUuid, string $streetWithType, string $streetType, string $streetTypeFull, string $street): bool|int
    {
        if ($cityId && $settlementId)
            return false;

        if (!$cityId && !$settlementId)
            return false;

        if (trim($streetWithType) && trim($street)) {
            return $this->getDatabase()->modify(
                "update addresses_streets set address_city_id = :address_city_id, address_settlement_id = :address_settlement_id, street_uuid = :street_uuid, street_with_type = :street_with_type, street_type = :street_type, street_type_full = :street_type_full, street = :street where address_street_id = $streetId",
                [
                    ":address_city_id" => $cityId ?: null,
                    ":address_settlement_id" => $settlementId ?: null,
                    ":street_uuid" => $streetUuid,
                    ":street_with_type" => $streetWithType,
                    ":street_type" => $streetType,
                    ":street_type_full" => $streetTypeFull,
                    ":street" => $street,
                ]
            );
        } else return false;
    }

    function addStreet(int|bool|null $cityId, int|bool|null $settlementId, string $streetUuid, string $streetWithType, string $streetType, string $streetTypeFull, string $street): bool|int|string
    {
        if ($cityId && $settlementId)
            return false;

        if (!$cityId && !$settlementId)
            return false;

        if (trim($streetWithType) && trim($street)) {
            return $this->getDatabase()->insert(
                "insert into addresses_streets (address_city_id, address_settlement_id, street_uuid, street_with_type, street_type, street_type_full, street) values (:address_city_id, :address_settlement_id, :street_uuid, :street_with_type, :street_type, :street_type_full, :street)",
                [
                    ":address_city_id" => $cityId ?: null,
                    ":address_settlement_id" => $settlementId ?: null,
                    ":street_uuid" => $streetUuid,
                    ":street_with_type" => $streetWithType,
                    ":street_type" => $streetType,
                    ":street_type_full" => $streetTypeFull,
                    ":street" => $street,
                ]
            );
        } else return false;
    }

    function deleteStreet(int $streetId): bool|int
    {
        return $this->getDatabase()->modify("delete from addresses_streets where address_street_id = $streetId");
    }

    function getHouses(int|bool|null $settlementId = false, int|bool|null $streetId = false): bool|array
    {
        if ($settlementId && $streetId)
            return false;

        if ($settlementId) {
            $query = "select address_house_id, address_settlement_id, address_street_id, house_uuid, house_type, house_type_full, house_full, house from addresses_houses where address_settlement_id = $settlementId and coalesce(address_street_id, 0) = 0 order by house";
        } else {
            if ($streetId)
                $query = "select address_house_id, address_settlement_id, address_street_id, house_uuid, house_type, house_type_full, house_full, house from addresses_houses where address_street_id = $streetId and coalesce(addresses_houses.address_settlement_id, 0) = 0 order by house";
            else
                $query = "select address_house_id, address_settlement_id, address_street_id, house_uuid, house_type, house_type_full, house_full, house from addresses_houses order by house";
        }

        return $this->getDatabase()->get(
            $query,
            map: [
                "address_house_id" => "houseId",
                "address_settlement_id" => "settlementId",
                "address_street_id" => "streetId",
                "house_uuid" => "houseUuid",
                "house_type" => "houseType",
                "house_type_full" => "houseTypeFull",
                "house_full" => "houseFull",
                "house" => "house",
            ]
        );
    }

    function getHouse(int $houseId): bool|array
    {
        return $this->getDatabase()->get(
            "select address_house_id, address_settlement_id, address_street_id, house_uuid, house_type, house_type_full, house_full, house from addresses_houses where address_house_id = $houseId",
            map: [
                "address_house_id" => "houseId",
                "address_settlement_id" => "settlementId",
                "address_street_id" => "streetId",
                "house_uuid" => "houseUuid",
                "house_type" => "houseType",
                "house_type_full" => "houseTypeFull",
                "house_full" => "houseFull",
                "house" => "house",
            ],
            options: ['singlify']
        );
    }

    function modifyHouse(int $houseId, int|bool|null $settlementId, int|bool|null $streetId, string $houseUuid, string $houseType, string $houseTypeFull, string $houseFull, string $house): bool|int
    {
        if ($settlementId && $streetId)
            return false;

        if (!$settlementId && !$streetId)
            return false;

        if (trim($houseFull) && trim($house)) {
            return $this->getDatabase()->modify("update addresses_houses set address_settlement_id = :address_settlement_id, address_street_id = :address_street_id, house_uuid = :house_uuid, house_type = :house_type, house_type_full = :house_type_full, house_full = :house_full, house = :house where address_house_id = $houseId", [
                ":address_settlement_id" => $settlementId ?: null,
                ":address_street_id" => $streetId ?: null,
                ":house_uuid" => $houseUuid,
                ":house_type" => $houseType,
                ":house_type_full" => $houseTypeFull,
                ":house_full" => $houseFull,
                ":house" => $house,
            ]);
        } else return false;
    }

    function addHouse(int|bool|null $settlementId, int|bool|null $streetId, string $houseUuid, string $houseType, string $houseTypeFull, string $houseFull, string $house): bool|int|string
    {
        if ($settlementId && $streetId)
            return false;

        if (!$settlementId && !$streetId)
            return false;

        if (trim($houseFull) && trim($house)) {
            return $this->getDatabase()->insert("insert into addresses_houses (address_settlement_id, address_street_id, house_uuid, house_type, house_type_full, house_full, house) values (:address_settlement_id, :address_street_id, :house_uuid, :house_type, :house_type_full, :house_full, :house)", [
                ":address_settlement_id" => $settlementId ?: null,
                ":address_street_id" => $streetId ?: null,
                ":house_uuid" => $houseUuid,
                ":house_type" => $houseType,
                ":house_type_full" => $houseTypeFull,
                ":house_full" => $houseFull,
                ":house" => $house,
            ]);
        } else return false;
    }

    function deleteHouse(int $houseId): bool|int
    {
        return $this->getDatabase()->modify("delete from addresses_houses where address_house_id = $houseId");
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws RedisException
     */
    function addHouseByMagic(string $houseUuid): bool|int
    {
        $house = $this->getRedis()->getConnection()->get("house_" . $houseUuid);

        if ($house) {
            $house = json_decode($house, true);

            $regionId = null;

            if ($house["data"]["region_fias_id"]) {
                $regionId = $this->getDatabase()->get("select address_region_id from addresses_regions where region_uuid = :region_uuid or region = :region",
                    ["region_uuid" => $house["data"]["region_fias_id"], "region" => $house["data"]["region"]],
                    options: ["fieldlify"]
                );

                if (!$regionId)
                    $regionId = $this->addRegion($house["data"]["region_fias_id"], $house["data"]["region_iso_code"], $house["data"]["region_with_type"], $house["data"]["region_type"], $house["data"]["region_type_full"], $house["data"]["region"]);
            }

            if (!$regionId) {
                error_log($house["data"]["house_fias_id"] . " no region");

                return false;
            }

            $areaId = null;

            if ($house["data"]["area_fias_id"]) {
                $areaId = $this->getDatabase()->get(
                    "select address_area_id from addresses_areas where area_uuid = :area_uuid or (address_region_id = :address_region_id and area = :area)",
                    ["area_uuid" => $house["data"]["area_fias_id"], "address_region_id" => $regionId, "area" => $house["data"]["area"]],
                    options: ["fieldlify"]
                );

                if (!$areaId)
                    $areaId = $this->addArea($regionId, $house["data"]["area_fias_id"], $house["data"]["area_with_type"], $house["data"]["area_type"], $house["data"]["area_type_full"], $house["data"]["area"]);
            }

            if ($areaId)
                $regionId = null;

            $cityId = null;

            if ($house["data"]["city_fias_id"]) {
                $cityId = $this->getDatabase()->get(
                    "select address_city_id from addresses_cities where city_uuid = :city_uuid or (address_region_id = :address_region_id and city = :city) or (address_area_id = :address_area_id and city = :city)",
                    ["city_uuid" => $house["data"]["city_fias_id"], "address_region_id" => $regionId, "address_area_id" => $areaId, "city" => $house["data"]["city"]],
                    options: ['fieldlify']
                );

                if (!$cityId)
                    $cityId = $this->addCity($regionId, $areaId, $house["data"]["city_fias_id"], $house["data"]["city_with_type"], $house["data"]["city_type"], $house["data"]["city_type_full"], $house["data"]["city"]);
            }

            if ($cityId)
                $areaId = null;

            if (!$areaId && !$cityId) {
                error_log($house["data"]["house_fias_id"] . " no area or city");

                return false;
            }

            $settlementId = null;

            if ($house["data"]["settlement_fias_id"]) {
                $settlementId = $this->getDatabase()->get(
                    "select address_settlement_id from addresses_settlements where settlement_uuid = :settlement_uuid or (address_area_id = :address_area_id and settlement = :settlement) or (address_city_id = :address_city_id and settlement = :settlement)",
                    ["settlement_uuid" => $house["data"]["settlement_fias_id"], "address_area_id" => $areaId, "address_city_id" => $cityId, "settlement" => $house["data"]["settlement"]],
                    options: ['fieldlify']
                );

                if (!$settlementId)
                    $settlementId = $this->addSettlement($areaId, $cityId, $house["data"]["settlement_fias_id"], $house["data"]["settlement_with_type"], $house["data"]["settlement_type"], $house["data"]["settlement_type_full"], $house["data"]["settlement"]);
            }

            if ($settlementId)
                $cityId = null;

            if (!$cityId && !$settlementId) {
                error_log($house["data"]["house_fias_id"] . " no city or settlement");

                return false;
            }

            $streetId = null;

            if ($house["data"]["street_fias_id"]) {
                $streetId = $this->getDatabase()->get(
                    "select address_street_id from addresses_streets where street_uuid = :street_uuid or (address_city_id = :address_city_id and street = :street) or (address_settlement_id = :address_settlement_id and street = :street)",
                    ["street_uuid" => $house["data"]["street_fias_id"], "address_city_id" => $cityId, "address_settlement_id" => $settlementId, "street" => $house["data"]["street"]],
                    options: ['fieldlify']
                );

                if (!$streetId)
                    $streetId = $this->addStreet($cityId, $settlementId, $house["data"]["street_fias_id"], $house["data"]["street_with_type"], $house["data"]["street_type"], $house["data"]["street_type_full"], $house["data"]["street"]);
            }

            if ($streetId)
                $settlementId = null;

            if (!$settlementId && !$streetId) {
                error_log($house['data']['house_fias_id'] . ' no setllement or street');

                return false;
            }

            $houseId = null;

            if ($house["data"]["house_fias_id"]) {
                $houseId = $this->getDatabase()->get(
                    "select address_house_id from addresses_houses where house_uuid = :house_uuid or (address_settlement_id = :address_settlement_id and house = :house) or (address_street_id = :address_street_id and house = :house)",
                    ["house_uuid" => $house["data"]["house_fias_id"], "address_settlement_id" => $settlementId, "address_street_id" => $streetId, "house" => $house["data"]["house"]],
                    options: ['fieldlify']
                );

                if (!$houseId)
                    $houseId = $this->addHouse($settlementId, $streetId, $house["data"]["house_fias_id"], $house["data"]["house_type"], $house["data"]["house_type_full"], $house["value"], $house["data"]["house"]);
            }

            if ($houseId) return $houseId;
            else {
                error_log($house['data']['house_fias_id'] . ' no house');

                return false;
            }
        } else return false;
    }
}