ALTER TABLE dvr_servers
    DROP COLUMN credentials;

ALTER TABLE cameras
    DROP CONSTRAINT fk_cameras_dvr_server_id;

ALTER TABLE cameras
    DROP CONSTRAINT fk_cameras_frs_server_id;

ALTER TABLE cameras
    DROP COLUMN dvr_server_id;

ALTER TABLE cameras
    DROP COLUMN frs_server_id;
