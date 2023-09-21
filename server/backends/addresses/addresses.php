<?php

/**
 * backends addresses namespace
 */

namespace backends\addresses;

use backends\backend;

/**
 * base addresses class
 */
abstract class addresses extends backend
{
    abstract function getRegions(): bool|array;

    abstract function getRegion(int $regionId): bool|array;

    abstract function modifyRegion(int $regionId, string $regionUuid, string $regionIsoCode, string $regionWithType, string $regionType, string $regionTypeFull, string $region, ?string $timezone = "-"): bool;

    abstract function addRegion(string $regionUuid, string $regionIsoCode, string $regionWithType, string $regionType, string $regionTypeFull, string $region, ?string $timezone = "-"): bool|int;

    abstract function deleteRegion(int $regionId): bool;

    abstract function getAreas(?int $regionId): bool|array;

    abstract function getArea(int $areaId): bool|array;

    /**
     * @param $areaId
     * @param $regionId
     * @param $areaUuid
     * @param $areaWithType
     * @param $areaType
     * @param $areaTypeFull
     * @param $area
     * @return boolean
     */
    abstract function modifyArea(int|bool|null $areaId, int|bool|null $regionId, $areaUuid, $areaWithType, $areaType, $areaTypeFull, $area, $timezone = "-");

    /**
     * @param $regionId
     * @param $areaUuid
     * @param $areaWithType
     * @param $areaType
     * @param $areaTypeFull
     * @param $area
     * @return false|integer
     */
    abstract function addArea(int $regionId, $areaUuid, $areaWithType, $areaType, $areaTypeFull, $area, $timezone = "-");

    abstract function deleteArea(int $areaId): bool|int;

    abstract function getCities(int|bool $regionId = false, int|bool $areaId = false): bool|array;

    abstract function getCity(int $cityId): bool|array;

    /**
     * @param $cityId
     * @param $regionId
     * @param $areaId
     * @param $cityUuid
     * @param $cityWithType
     * @param $cityType
     * @param $cityTypeFull
     * @param $city
     * @return boolean
     */
    abstract function modifyCity(int|bool|null $cityId, int|bool|null $regionId, int|bool|null $areaId, $cityUuid, $cityWithType, $cityType, $cityTypeFull, $city, $timezone = "-");

    /**
     * @param $regionId
     * @param $areaId
     * @param $cityUuid
     * @param $cityWithType
     * @param $cityType
     * @param $cityTypeFull
     * @param $city
     * @return false|integer
     */
    abstract function addCity(int $regionId, int $areaId, $cityUuid, $cityWithType, $cityType, $cityTypeFull, $city, $timezone = "-");

    abstract function deleteCity(int $cityId): bool|int;

    abstract function getSettlements(int|bool $areaId = false, int|bool $cityId = false): array|bool;

    abstract function getSettlement(int $settlementId): array|bool;

    /**
     * @param $settlementId
     * @param $areaId
     * @param $cityId
     * @param $settlementUuid
     * @param $settlementWithType
     * @param $settlementType
     * @param $settlementTypeFull
     * @param $settlement
     * @return boolean
     */
    abstract function modifySettlement(int|bool|null $settlementId, int|bool|null $areaId, int|bool|null $cityId, $settlementUuid, $settlementWithType, $settlementType, $settlementTypeFull, $settlement);

    /**
     * @param $areaId
     * @param $cityId
     * @param $settlementUuid
     * @param $settlementWithType
     * @param $settlementType
     * @param $settlementTypeFull
     * @param $settlement
     * @return false|integer
     */
    abstract function addSettlement(int|bool|null $areaId, int|bool|null $cityId, $settlementUuid, $settlementWithType, $settlementType, $settlementTypeFull, $settlement);

    /**
     * @param $settlementId
     * @return boolean
     */
    abstract function deleteSettlement(int $settlementId);

    /**
     * @param $cityId
     * @param $settlementId
     * @return false|array
     */
    abstract function getStreets(int|bool $cityId = false, int|bool $settlementId = false);

    /**
     * @param $streetId
     * @return false|array
     */
    abstract function getStreet(int $streetId);

    /**
     * @param $streetId
     * @param $cityId
     * @param $settlementId
     * @param $streetUuid
     * @param $streetWithType
     * @param $streetType
     * @param $streetTypeFull
     * @param $street
     * @return boolean
     */
    abstract function modifyStreet(int $streetId, int|bool|null $cityId, int|bool|null $settlementId, $streetUuid, $streetWithType, $streetType, $streetTypeFull, $street);

    /**
     * @param $cityId
     * @param $settlementId
     * @param $streetUuid
     * @param $streetWithType
     * @param $streetType
     * @param $streetTypeFull
     * @param $street
     * @return false|integer
     */
    abstract function addStreet(int|bool|null $cityId, int|bool|null $settlementId, $streetUuid, $streetWithType, $streetType, $streetTypeFull, $street);

    /**
     * @param $streetId
     * @return boolean
     */
    abstract function deleteStreet(int $streetId);

    /**
     * @param $settlementId
     * @param $streetId
     * @return false|array
     */
    abstract function getHouses(int|bool|null  $settlementId = false, int|bool|null  $streetId = false);

    /**
     * @param $houseId
     * @return false|array
     */
    abstract function getHouse(int $houseId);

    /**
     * @param $houseId
     * @param $settlementId
     * @param $streetId
     * @param $houseUuid
     * @param $houseType
     * @param $houseTypeFull
     * @param $houseFull
     * @param $house
     * @return boolean
     */
    abstract function modifyHouse(int $houseId, int|bool|null $settlementId, int|bool|null $streetId, $houseUuid, $houseType, $houseTypeFull, $houseFull, $house);

    /**
     * @param $settlementId
     * @param $streetId
     * @param $houseUuid
     * @param $houseType
     * @param $houseTypeFull
     * @param $houseFull
     * @param $house
     * @return false|integer
     */
    abstract function addHouse(int|bool|null $settlementId, int|bool|null $streetId, $houseUuid, $houseType, $houseTypeFull, $houseFull, $house);

    /**
     * @param $houseId
     * @return boolean
     */
    abstract function deleteHouse(int $houseId);

    /**
     * @param $houseUuid
     * @return false|integer
     */
    abstract function addHouseByMagic($houseUuid);
}
