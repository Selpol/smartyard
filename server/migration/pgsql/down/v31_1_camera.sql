ALTER TABLE cameras
    DROP device_id,
    DROP device_model,
    DROP device_software_version,
    DROP device_hardware_version,
    DROP config;

ALTER TABLE cameras
    ADD COLUMN screenshot VARCHAR,
    ADD COLUMN direction  REAL,
    ADD COLUMN angle      REAL,
    ADD COLUMN distance   REAL,
    ADD COLUMN md_left    INTEGER,
    ADD COLUMN md_top     INTEGER,
    ADD COLUMN md_width   INTEGER,
    ADD COLUMN md_height  INTEGER;