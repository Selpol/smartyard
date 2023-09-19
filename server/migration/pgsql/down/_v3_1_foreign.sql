ALTER TABLE houses_cameras_houses
    DROP CONSTRAINT fk_houses_cameras_houses_camera_id;

ALTER TABLE houses_cameras_flats
    DROP CONSTRAINT fk_houses_cameras_flats_camera_id;

ALTER TABLE houses_cameras_flats
    DROP CONSTRAINT fk_houses_cameras_flats_house_flat_id;

ALTER TABLE houses_cameras_subscribers
    DROP CONSTRAINT fk_houses_cameras_subscribers_camera_id;

ALTER TABLE houses_flats
    DROP CONSTRAINT fk_houses_flats_address_house_id;

ALTER TABLE houses_houses_entrances
    DROP CONSTRAINT fk_houses_houses_entrances_address_house_id;

ALTER TABLE houses_entrances_flats
    DROP CONSTRAINT fk_houses_entrances_flats_house_flat_id;

ALTER TABLE houses_flats_subscribers
    DROP CONSTRAINT fk_houses_flats_subscribers_house_flat_id;

ALTER TABLE houses_entrances
    DROP CONSTRAINT fk_houses_entrances_camera_id;

ALTER TABLE houses_entrances_cmses
    DROP CONSTRAINT fk_houses_entrances_cmses_house_entrance_id;

ALTER TABLE houses_houses_entrances
    DROP CONSTRAINT fk_houses_houses_entrances_house_entrance_id;
