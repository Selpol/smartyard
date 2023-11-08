ALTER TABLE addresses_areas
    ADD CONSTRAINT fk_addresses_areas_address_region_id FOREIGN KEY (address_region_id) REFERENCES addresses_regions (address_region_id) ON DELETE CASCADE;

ALTER TABLE addresses_cities
    ADD CONSTRAINT fk_addresses_cities_address_region_id FOREIGN KEY (address_region_id) REFERENCES addresses_regions (address_region_id) ON DELETE CASCADE;

ALTER TABLE addresses_cities
    ADD CONSTRAINT fk_addresses_cities_address_area_id FOREIGN KEY (address_area_id) REFERENCES addresses_areas (address_area_id) ON DELETE CASCADE;

ALTER TABLE addresses_settlements
    ADD CONSTRAINT fk_addresses_settlements_address_area_id FOREIGN KEY (address_area_id) REFERENCES addresses_areas (address_area_id) ON DELETE CASCADE;

ALTER TABLE addresses_settlements
    ADD CONSTRAINT fk_addresses_settlements_address_city_id FOREIGN KEY (address_city_id) REFERENCES addresses_cities (address_city_id) ON DELETE CASCADE;

ALTER TABLE addresses_streets
    ADD CONSTRAINT fk_addresses_streets_address_city_id FOREIGN KEY (address_city_id) REFERENCES addresses_cities (address_city_id) ON DELETE CASCADE;

ALTER TABLE addresses_streets
    ADD CONSTRAINT fk_addresses_streets_address_settlement_id FOREIGN KEY (address_settlement_id) REFERENCES addresses_settlements (address_settlement_id) ON DELETE CASCADE;

ALTER TABLE addresses_houses
    ADD CONSTRAINT fk_addresses_houses_address_settlement_id FOREIGN KEY (address_settlement_id) REFERENCES addresses_settlements (address_settlement_id) ON DELETE CASCADE;

ALTER TABLE addresses_houses
    ADD CONSTRAINT fk_addresses_houses_address_street_id FOREIGN KEY (address_street_id) REFERENCES addresses_streets (address_street_id) ON DELETE CASCADE;

ALTER TABLE houses_entrances
    ADD CONSTRAINT fk_houses_entrances_camera_id FOREIGN KEY (camera_id) REFERENCES cameras (camera_id) ON DELETE CASCADE;

ALTER TABLE houses_entrances
    ADD CONSTRAINT fk_houses_entrances_house_domophone_id FOREIGN KEY (house_domophone_id) REFERENCES houses_domophones (house_domophone_id) ON DELETE CASCADE;

ALTER TABLE houses_entrances_cmses
    ADD CONSTRAINT fk_houses_entrances_cmses_house_entrance_id FOREIGN KEY (house_entrance_id) REFERENCES houses_entrances (house_entrance_id) ON DELETE CASCADE;

ALTER TABLE houses_houses_entrances
    ADD CONSTRAINT fk_houses_houses_entrances_address_house_id FOREIGN KEY (address_house_id) REFERENCES addresses_houses (address_house_id) ON DELETE CASCADE;

ALTER TABLE houses_houses_entrances
    ADD CONSTRAINT fk_houses_houses_entrances_house_entrance_id FOREIGN KEY (house_entrance_id) REFERENCES houses_entrances (house_entrance_id) ON DELETE CASCADE;

ALTER TABLE houses_flats
    ADD CONSTRAINT fk_houses_flats_address_house_id FOREIGN KEY (address_house_id) REFERENCES addresses_houses (address_house_id) ON DELETE CASCADE;

ALTER TABLE houses_entrances_flats
    ADD CONSTRAINT fk_houses_entrances_flats_house_entrance_id FOREIGN KEY (house_entrance_id) REFERENCES houses_entrances (house_entrance_id) ON DELETE CASCADE;

ALTER TABLE houses_entrances_flats
    ADD CONSTRAINT fk_houses_entrances_flats_house_flat_id FOREIGN KEY (house_flat_id) REFERENCES houses_flats (house_flat_id) ON DELETE CASCADE;

ALTER TABLE houses_flats_subscribers
    ADD CONSTRAINT fk_houses_flats_subscribers_house_flat_id FOREIGN KEY (house_flat_id) REFERENCES houses_flats (house_flat_id) ON DELETE CASCADE;

ALTER TABLE houses_flats_subscribers
    ADD CONSTRAINT fk_houses_flats_subscribers_house_subscriber_id FOREIGN KEY (house_subscriber_id) REFERENCES houses_subscribers_mobile (house_subscriber_id) ON DELETE CASCADE;

ALTER TABLE houses_cameras_houses
    ADD CONSTRAINT fk_houses_cameras_houses_camera_id FOREIGN KEY (camera_id) REFERENCES cameras (camera_id) ON DELETE CASCADE;

ALTER TABLE houses_cameras_houses
    ADD CONSTRAINT fk_houses_cameras_houses_address_house_id FOREIGN KEY (address_house_id) REFERENCES addresses_houses (address_house_id) ON DELETE CASCADE;

ALTER TABLE houses_cameras_flats
    ADD CONSTRAINT fk_houses_cameras_flats_camera_id FOREIGN KEY (camera_id) REFERENCES cameras (camera_id) ON DELETE CASCADE;

ALTER TABLE houses_cameras_flats
    ADD CONSTRAINT fk_houses_cameras_flats_house_flat_id FOREIGN KEY (house_flat_id) REFERENCES houses_flats (house_flat_id) ON DELETE CASCADE;

ALTER TABLE houses_cameras_subscribers
    ADD CONSTRAINT fk_houses_cameras_subscribers_camera_id FOREIGN KEY (camera_id) REFERENCES cameras (camera_id) ON DELETE CASCADE;

ALTER TABLE houses_cameras_subscribers
    ADD CONSTRAINT fk_houses_cameras_subscribers_house_subscriber_id FOREIGN KEY (house_subscriber_id) REFERENCES houses_subscribers_mobile (house_subscriber_id) ON DELETE CASCADE;

ALTER TABLE inbox
    ADD CONSTRAINT fk_inbox_house_subscriber_id FOREIGN KEY (house_subscriber_id) REFERENCES houses_subscribers_mobile (house_subscriber_id) ON DELETE CASCADE;

ALTER TABLE frs_links_faces
    ADD CONSTRAINT fk_frs_links_faces_flat_id FOREIGN KEY (flat_id) REFERENCES houses_flats (house_flat_id) ON DELETE CASCADE;

ALTER TABLE frs_links_faces
    ADD CONSTRAINT fk_frs_links_faces_house_subscriber_id FOREIGN KEY (house_subscriber_id) REFERENCES houses_subscribers_mobile (house_subscriber_id) ON DELETE CASCADE;

ALTER TABLE frs_links_faces
    ADD CONSTRAINT fk_frs_links_faces_face_id FOREIGN KEY (face_id) REFERENCES frs_faces (face_id) ON DELETE CASCADE;

ALTER TABLE audit
    ADD CONSTRAINT fk_audit_user_id FOREIGN KEY (user_id) REFERENCES core_users (uid);