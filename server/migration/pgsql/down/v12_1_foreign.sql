ALTER TABLE addresses_areas
    DROP CONSTRAINT fk_addresses_areas_address_region_id;

ALTER TABLE addresses_cities
    DROP CONSTRAINT fk_addresses_cities_address_region_id;

ALTER TABLE addresses_cities
    DROP CONSTRAINT fk_addresses_cities_address_area_id;

ALTER TABLE addresses_settlements
    DROP CONSTRAINT fk_addresses_settlements_address_area_id;

ALTER TABLE addresses_settlements
    DROP CONSTRAINT fk_addresses_settlements_address_city_id;

ALTER TABLE addresses_streets
    DROP CONSTRAINT fk_addresses_streets_address_city_id;

ALTER TABLE addresses_streets
    DROP CONSTRAINT fk_addresses_streets_address_settlement_id;

ALTER TABLE addresses_houses
    DROP CONSTRAINT fk_addresses_houses_address_settlement_id;

ALTER TABLE addresses_houses
    DROP CONSTRAINT fk_addresses_houses_address_street_id;

ALTER TABLE houses_entrances
    DROP CONSTRAINT fk_houses_entrances_camera_id;

ALTER TABLE houses_entrances
    DROP CONSTRAINT fk_houses_entrances_house_domophone_id;

ALTER TABLE houses_entrances_cmses
    DROP CONSTRAINT fk_houses_entrances_cmses_house_entrance_id;

ALTER TABLE houses_houses_entrances
    DROP CONSTRAINT fk_houses_houses_entrances_address_house_id;

ALTER TABLE houses_houses_entrances
    DROP CONSTRAINT fk_houses_houses_entrances_house_entrance_id;

ALTER TABLE houses_flats
    DROP CONSTRAINT fk_houses_flats_address_house_id;

ALTER TABLE houses_entrances_flats
    DROP CONSTRAINT fk_houses_entrances_flats_house_entrance_id;

ALTER TABLE houses_entrances_flats
    DROP CONSTRAINT fk_houses_entrances_flats_house_flat_id;

ALTER TABLE houses_flats_subscribers
    DROP CONSTRAINT fk_houses_flats_subscribers_house_flat_id;

ALTER TABLE houses_flats_subscribers
    DROP CONSTRAINT fk_houses_flats_subscribers_house_subscriber_id;

ALTER TABLE houses_cameras_houses
    DROP CONSTRAINT fk_houses_cameras_houses_camera_id;

ALTER TABLE houses_cameras_houses
    DROP CONSTRAINT fk_houses_cameras_houses_address_house_id;

ALTER TABLE houses_cameras_flats
    DROP CONSTRAINT fk_houses_cameras_flats_camera_id;

ALTER TABLE houses_cameras_flats
    DROP CONSTRAINT fk_houses_cameras_flats_house_flat_id;

ALTER TABLE houses_cameras_subscribers
    DROP CONSTRAINT fk_houses_cameras_subscribers_camera_id;

ALTER TABLE houses_cameras_subscribers
    DROP CONSTRAINT fk_houses_cameras_subscribers_house_subscriber_id;

ALTER TABLE inbox
    DROP CONSTRAINT fk_inbox_house_subscriber_id;

ALTER TABLE frs_links_faces
    DROP CONSTRAINT fk_frs_links_faces_flat_id;

ALTER TABLE frs_links_faces
    DROP CONSTRAINT fk_frs_links_faces_house_subscriber_id;

ALTER TABLE frs_links_faces
    DROP CONSTRAINT fk_frs_links_faces_face_id;

ALTER TABLE audit
    DROP CONSTRAINT fk_audit_user_id;