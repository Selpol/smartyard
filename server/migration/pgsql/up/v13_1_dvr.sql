ALTER TABLE dvr_servers
    ADD COLUMN credentials VARCHAR;

ALTER TABLE cameras
    ADD COLUMN dvr_server_id INT;

ALTER TABLE cameras
    ADD COLUMN frs_server_id INT;

ALTER TABLE cameras
    ADD CONSTRAINT fk_cameras_dvr_server_id FOREIGN KEY (dvr_server_id) REFERENCES dvr_servers (id) ON DELETE CASCADE;

ALTER TABLE cameras
    ADD CONSTRAINT fk_cameras_frs_server_id FOREIGN KEY (frs_server_id) REFERENCES frs_servers (id) ON DELETE CASCADE;
