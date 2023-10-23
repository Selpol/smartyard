<?php

namespace Selpol\Feature\Address;

use Selpol\Feature\Address\Internal\InternalAddressFeature;
use Selpol\Feature\Feature;
use Selpol\Framework\Container\Attribute\Singleton;

#[Singleton(InternalAddressFeature::class)]
readonly abstract class AddressFeature extends Feature
{
    abstract function getRegions(): bool|array;

    abstract function getRegion(int $regionId): bool|array;

    abstract function modifyRegion(int $regionId, string $regionUuid, string $regionIsoCode, string $regionWithType, string $regionType, string $regionTypeFull, string $region, ?string $timezone = "-"): bool;

    abstract function addRegion(string $regionUuid, string $regionIsoCode, string $regionWithType, string $regionType, string $regionTypeFull, string $region, ?string $timezone = "-"): bool|int;

    abstract function deleteRegion(int $regionId): bool;

    abstract function getAreas(?int $regionId): bool|array;

    abstract function getArea(int $areaId): bool|array;

    abstract function modifyArea(int|bool|null $areaId, int|bool|null $regionId, string $areaUuid, string $areaWithType, string $areaType, string $areaTypeFull, string $area, string $timezone = "-"): bool|int;

    abstract function addArea(int $regionId, string $areaUuid, string $areaWithType, string $areaType, string $areaTypeFull, string $area, string $timezone = "-"): bool|int|string;

    abstract function deleteArea(int $areaId): bool|int;

    abstract function getCities(int|bool $regionId = false, int|bool $areaId = false): bool|array;

    abstract function getCity(int $cityId): bool|array;

    abstract function modifyCity(int|bool|null $cityId, int|bool|null $regionId, int|bool|null $areaId, string $cityUuid, string $cityWithType, string $cityType, string $cityTypeFull, string $city, string $timezone = "-"): bool|int;

    abstract function addCity(int $regionId, ?int $areaId, string $cityUuid, string $cityWithType, string $cityType, string $cityTypeFull, string $city, string $timezone = "-"): bool|int|string;

    abstract function deleteCity(int $cityId): bool|int;

    abstract function getSettlements(int|bool $areaId = false, int|bool $cityId = false): array|bool;

    abstract function getSettlement(int $settlementId): array|bool;

    abstract function modifySettlement(int|bool|null $settlementId, int|bool|null $areaId, int|bool|null $cityId, string $settlementUuid, string $settlementWithType, string $settlementType, string $settlementTypeFull, string $settlement): bool|int;

    abstract function addSettlement(int|bool|null $areaId, int|bool|null $cityId, string $settlementUuid, string $settlementWithType, string $settlementType, string $settlementTypeFull, string $settlement): bool|int|string;

    abstract function deleteSettlement(int $settlementId): bool|int;

    abstract function getStreets(int|bool $cityId = false, int|bool $settlementId = false): bool|array;

    abstract function getStreet(int $streetId): bool|array;

    abstract function modifyStreet(int $streetId, int|bool|null $cityId, int|bool|null $settlementId, string $streetUuid, string $streetWithType, string $streetType, string $streetTypeFull, string $street): bool|int;

    abstract function addStreet(int|bool|null $cityId, int|bool|null $settlementId, string $streetUuid, string $streetWithType, string $streetType, string $streetTypeFull, string $street): bool|int|string;

    abstract function deleteStreet(int $streetId): bool|int;

    abstract function getHouses(int|bool|null $settlementId = false, int|bool|null $streetId = false): bool|array;

    abstract function getHouse(int $houseId): bool|array;

    abstract function modifyHouse(int $houseId, int|bool|null $settlementId, int|bool|null $streetId, string $houseUuid, string $houseType, string $houseTypeFull, string $houseFull, string $house): bool|int;

    abstract function addHouse(int|bool|null $settlementId, int|bool|null $streetId, string $houseUuid, string $houseType, string $houseTypeFull, string $houseFull, string $house): bool|int|string;

    abstract function deleteHouse(int $houseId): bool|int;

    abstract function addHouseByMagic(string $houseUuid): bool|int;
}