ALTER TABLE cameras
    ADD COLUMN device_id               VARCHAR(128),
    ADD COLUMN device_model            VARCHAR(64),
    ADD COLUMN device_software_version VARCHAR(64),
    ADD COLUMN device_hardware_version VARCHAR(64),
    ADD COLUMN config                  TEXT;

ALTER TABLE cameras
    DROP screenshot,
    DROP md_left,
    DROP md_top,
    DROP md_width,
    DROP md_height,
    DROP direction,
    DROP angle,
    DROP distance;