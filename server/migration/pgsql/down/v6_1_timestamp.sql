ALTER TABLE task
    ALTER COLUMN created_at TYPE TIMESTAMP;

ALTER TABLE task
    ALTER COLUMN updated_at TYPE TIMESTAMP;

ALTER TABLE audit
    ALTER COLUMN created_at TYPE TIMESTAMP;

ALTER TABLE audit
    ALTER COLUMN updated_at TYPE TIMESTAMP;

ALTER TABLE role
    ALTER COLUMN created_at TYPE TIMESTAMP;

ALTER TABLE role
    ALTER COLUMN updated_at TYPE TIMESTAMP;

ALTER TABLE permission
    ALTER COLUMN created_at TYPE TIMESTAMP;

ALTER TABLE permission
    ALTER COLUMN updated_at TYPE TIMESTAMP;

ALTER TABLE dvr_servers
    ALTER COLUMN created_at TYPE TIMESTAMP;

ALTER TABLE dvr_servers
    ALTER COLUMN updated_at TYPE TIMESTAMP;

ALTER TABLE frs_servers
    ALTER COLUMN created_at TYPE TIMESTAMP;

ALTER TABLE frs_servers
    ALTER COLUMN updated_at TYPE TIMESTAMP;
