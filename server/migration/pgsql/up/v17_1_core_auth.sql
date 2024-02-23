CREATE TABLE core_auth
(
    id             SERIAL PRIMARY KEY,

    user_id        INT       NOT NULL,

    user_agent     VARCHAR   NOT NULL,
    user_ip        VARCHAR   NOT NULL,

    remember_me    INT       NOT NULL,

    status         INT       NOT NULL,

    last_access_at TIMESTAMP NOT NULL DEFAULT NOW(),

    created_at     TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at     TIMESTAMP NOT NULL DEFAULT NOW(),

    CONSTRAINT fk_core_auth_user_id FOREIGN KEY (user_id) REFERENCES core_users (uid) ON DELETE CASCADE
);
