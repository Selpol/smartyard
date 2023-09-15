DROP INDEX addresses_houses_house_uuid;
DROP INDEX addresses_houses_house;
DROP INDEX addresses_houses_address_settlement_id;
DROP INDEX addresses_houses_address_street_id;
DROP INDEX addresses_houses_house_full;

DROP TABLE addresses_houses;

DROP INDEX addresses_streets_street_uuid;
DROP INDEX addresses_streets_street;
DROP INDEX addresses_streets_address_address_settlement_id;
DROP INDEX addresses_streets_address_address_city_id;

DROP TABLE addresses_streets;

DROP INDEX addresses_settlements_settlement_uuid;
DROP INDEX addresses_settlements_settlement;
DROP INDEX addresses_settlements_address_region_id;
DROP INDEX addresses_settlements_address_area_id;

DROP TABLE addresses_settlements;

DROP INDEX addresses_cities_city_uuid;
DROP INDEX addresses_cities_city;
DROP INDEX addresses_cities_address_region_id;
DROP INDEX addresses_cities_address_area_id;

DROP TABLE addresses_cities;

DROP INDEX addresses_areas_area_uuid;
DROP INDEX addresses_areas_area;
DROP INDEX addresses_areas_address_region_id;

DROP TABLE addresses_areas;

DROP INDEX addresses_regions_region_uuid;
DROP INDEX addresses_regions_region;

DROP TABLE addresses_regions;
