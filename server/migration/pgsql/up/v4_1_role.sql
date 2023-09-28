CREATE SEQUENCE role_id_seq INCREMENT BY 1 MINVALUE 1 START 1;

CREATE TABLE role
(
    id          INT       NOT NULL,

    title       VARCHAR   NOT NULL,
    description VARCHAR   NOT NULL,

    created_at  TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMP NOT NULL DEFAULT NOW(),

    PRIMARY KEY (id)
);

CREATE SEQUENCE permission_id_seq INCREMENT BY 1 MINVALUE 1 START 1;

CREATE TABLE permission
(
    id          INT       NOT NULL,

    title       VARCHAR   NOT NULL,
    description VARCHAR   NOT NULL,

    created_at  TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMP NOT NULL DEFAULT NOW(),

    PRIMARY KEY (id)

);

CREATE TABLE role_permission
(
    role_id       INT NOT NULL,
    permission_id INT NOT NULL
);

ALTER TABLE role_permission
    ADD CONSTRAINT fk_role_permission_role_id FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE role_permission
    ADD CONSTRAINT fk_role_permission_permission_id FOREIGN KEY (permission_id) REFERENCES permission (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

CREATE TABLE user_role
(
    user_id INT NOT NULL,
    role_id INT NOT NULL
);

ALTER TABLE user_role
    ADD CONSTRAINT fk_user_role_user_id FOREIGN KEY (user_id) REFERENCES core_users (uid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE user_role
    ADD CONSTRAINT fk_user_role_role_id FOREIGN KEY (role_id) REFERENCES role (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

CREATE TABLE user_permission
(
    user_id       INT NOT NULL,
    permission_id INT NOT NULL
);

ALTER TABLE user_permission
    ADD CONSTRAINT fk_user_permission_user_id FOREIGN KEY (user_id) REFERENCES core_users (uid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;

ALTER TABLE user_permission
    ADD CONSTRAINT fk_user_permission_permission_id FOREIGN KEY (permission_id) REFERENCES permission (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
