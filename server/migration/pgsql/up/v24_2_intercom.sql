ALTER TABLE houses_domophones
    ADD COLUMN device_id VARCHAR(128);

ALTER TABLE houses_domophones
    ADD COLUMN device_model VARCHAR(64);

ALTER TABLE houses_domophones
    ADD COLUMN device_software_version VARCHAR(64);

ALTER TABLE houses_domophones
    ADD COLUMN device_hardware_version VARCHAR(64);