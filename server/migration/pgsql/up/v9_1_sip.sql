CREATE SEQUENCE sip_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1;

CREATE TABLE sip_user
(
    id         INT       NOT NULL,

    type       INT       NOT NULL,

    title      VARCHAR   NOT NULL,

    password   VARCHAR   NOT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),

    PRIMARY KEY (id)
);
