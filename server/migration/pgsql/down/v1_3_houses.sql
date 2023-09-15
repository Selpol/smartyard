DROP INDEX houses_cameras_subscribers_uniq;
DROP INDEX houses_cameras_subscribers_camera_id;
DROP INDEX houses_cameras_subscribers_subscriber_id;

DROP TABLE houses_cameras_subscribers;

DROP INDEX houses_cameras_flats_uniq;
DROP INDEX houses_cameras_flats_camera_id;
DROP INDEX houses_cameras_flats_flat_id;

DROP TABLE houses_cameras_flats;

DROP INDEX houses_cameras_houses_uniq;
DROP INDEX houses_cameras_houses_id;
DROP INDEX houses_cameras_houses_house_id;

DROP TABLE houses_cameras_houses;

DROP INDEX houses_flats_subscribers_uniq;

DROP TABLE houses_flats_subscribers;

DROP INDEX subscribers_mobile_id;

DROP TABLE houses_subscribers_mobile;

DROP INDEX houses_rfids_uniq;

DROP TABLE houses_rfids;

DROP INDEX houses_entrances_flats_uniq;
DROP INDEX houses_entrances_flats_house_entrance_id;
DROP INDEX houses_entrances_flats_house_flat_id;

DROP TABLE houses_entrances_flats;

DROP INDEX houses_flats_uniq;
DROP INDEX houses_flats_address_house_id;

DROP TABLE houses_flats;

DROP INDEX houses_houses_entrances_uniq1;
DROP INDEX houses_houses_entrances_uniq2;
DROP INDEX houses_houses_entrances_address_house_id;
DROP INDEX houses_houses_entrances_house_entrance_id;
DROP INDEX houses_houses_entrances_prefix;

DROP TABLE houses_houses_entrances;

DROP INDEX houses_entrances_cmses_uniq1;
DROP INDEX houses_entrances_cmses_uniq2;

DROP TABLE houses_entrances_cmses;

DROP INDEX houses_entrances_uniq;
DROP INDEX houses_entrances_multihouse;

DROP TABLE houses_entrances;

DROP INDEX domophones_ip_port;

DROP TABLE houses_domophones;
