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
    abstract function modifyArea($areaId, $regionId, $areaUuid, $areaWithType, $areaType, $areaTypeFull, $area, $timezone = "-");

    /**
     * @param $regionId
     * @param $areaUuid
     * @param $areaWithType
     * @param $areaType
     * @param $areaTypeFull
     * @param $area
     * @return false|integer
     */
    abstract function addArea($regionId, $areaUuid, $areaWithType, $areaType, $areaTypeFull, $area, $timezone = "-");

    /**
     * @param $areaId
     * @return boolean
     */
    abstract function deleteArea($areaId);

    /**
     * @param $regionId
     * @param $areaId
     * @return false|array
     */
    abstract function getCities($regionId = false, $areaId = false);

    /**
     * @param $cityId
     * @return false|array
     */
    abstract function getCity($cityId);

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
    abstract function modifyCity($cityId, $regionId, $areaId, $cityUuid, $cityWithType, $cityType, $cityTypeFull, $city, $timezone = "-");

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
    abstract function addCity($regionId, $areaId, $cityUuid, $cityWithType, $cityType, $cityTypeFull, $city, $timezone = "-");

    /**
     * @param $cityId
     * @return boolean
     */
    abstract function deleteCity($cityId);

    /**
     * @param $areaId
     * @param $cityId
     * @return false|array
     */
    abstract function getSettlements($areaId = false, $cityId = false);

    /**
     * @param $settlementId
     * @return false|array
     */
    abstract function getSettlement($settlementId);

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
    abstract function modifySettlement($settlementId, $areaId, $cityId, $settlementUuid, $settlementWithType, $settlementType, $settlementTypeFull, $settlement);

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
    abstract function addSettlement($areaId, $cityId, $settlementUuid, $settlementWithType, $settlementType, $settlementTypeFull, $settlement);

    /**
     * @param $settlementId
     * @return boolean
     */
    abstract function deleteSettlement($settlementId);

    /**
     * @param $cityId
     * @param $settlementId
     * @return false|array
     */
    abstract function getStreets($cityId = false, $settlementId = false);

    /**
     * @param $streetId
     * @return false|array
     */
    abstract function getStreet($streetId);

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
    abstract function modifyStreet($streetId, $cityId, $settlementId, $streetUuid, $streetWithType, $streetType, $streetTypeFull, $street);

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
    abstract function addStreet($cityId, $settlementId, $streetUuid, $streetWithType, $streetType, $streetTypeFull, $street);

    /**
     * @param $streetId
     * @return boolean
     */
    abstract function deleteStreet($streetId);

    /**
     * @param $settlementId
     * @param $streetId
     * @return false|array
     */
    abstract function getHouses($settlementId = false, $streetId = false);

    /**
     * @param $houseId
     * @return false|array
     */
    abstract function getHouse($houseId);

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
    abstract function modifyHouse($houseId, $settlementId, $streetId, $houseUuid, $houseType, $houseTypeFull, $houseFull, $house);

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
    abstract function addHouse($settlementId, $streetId, $houseUuid, $houseType, $houseTypeFull, $houseFull, $house);

    /**
     * @param $houseId
     * @return boolean
     */
    abstract function deleteHouse($houseId);

    /**
     * @param $houseUuid
     * @return false|integer
     */
    abstract function addHouseByMagic($houseUuid);
}
