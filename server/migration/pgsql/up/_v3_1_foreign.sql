ALTER TABLE houses_cameras_houses
    ADD CONSTRAINT fk_houses_cameras_houses_camera_id FOREIGN KEY (camera_id) REFERENCES cameras (camera_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE houses_cameras_flats
    ADD CONSTRAINT fk_houses_cameras_flats_camera_id FOREIGN KEY (camera_id) REFERENCES cameras (camera_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE houses_cameras_flats
    ADD CONSTRAINT fk_houses_cameras_flats_house_flat_id FOREIGN KEY (house_flat_id) REFERENCES houses_flats (house_flat_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE houses_cameras_subscribers
    ADD CONSTRAINT fk_houses_cameras_subscribers_camera_id FOREIGN KEY (camera_id) REFERENCES cameras (camera_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE houses_flats
    ADD CONSTRAINT fk_houses_flats_address_house_id FOREIGN KEY (address_house_id) REFERENCES addresses_houses (address_house_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE houses_houses_entrances
    ADD CONSTRAINT fk_houses_houses_entrances_address_house_id FOREIGN KEY (address_house_id) REFERENCES addresses_houses (address_house_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE houses_entrances_flats
    ADD CONSTRAINT fk_houses_entrances_flats_house_flat_id FOREIGN KEY (house_flat_id) REFERENCES houses_flats (house_flat_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE houses_flats_subscribers
    ADD CONSTRAINT fk_houses_flats_subscribers_house_flat_id FOREIGN KEY (house_flat_id) REFERENCES houses_flats (house_flat_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE houses_entrances
    ADD CONSTRAINT fk_houses_entrances_camera_id FOREIGN KEY (camera_id) REFERENCES cameras (camera_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE houses_entrances_cmses
    ADD CONSTRAINT fk_houses_entrances_cmses_house_entrance_id FOREIGN KEY (house_entrance_id) REFERENCES houses_entrances (house_entrance_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE houses_houses_entrances
    ADD CONSTRAINT fk_houses_houses_entrances_house_entrance_id FOREIGN KEY (house_entrance_id) REFERENCES houses_entrances (house_entrance_id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
