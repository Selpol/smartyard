ALTER TABLE user_permission
    DROP CONSTRAINT fk_user_permission_permission_id;

ALTER TABLE user_permission
    DROP CONSTRAINT fk_user_permission_user_id;

DROP TABLE user_permission;

ALTER TABLE user_role
    DROP CONSTRAINT fk_user_role_role_id;

ALTER TABLE user_role
    DROP CONSTRAINT fk_user_role_user_id;

DROP TABLE user_role;

ALTER TABLE role_permission
    DROP CONSTRAINT fk_role_permission_permission_id;

ALTER TABLE role_permission
    DROP CONSTRAINT fk_role_permission_role_id;

DROP TABLE role_permission;

DROP SEQUENCE permission_id_seq;

DROP TABLE permission;

DROP SEQUENCE role_id_seq;

DROP TABLE role;
