CREATE SEQUENCE sip_servers_id_seq INCREMENT BY 1 MINVALUE 1 START 1;

CREATE TABLE sip_servers
(
    id          INT       NOT NULL,

    title       VARCHAR   NOT NULL,
    type        VARCHAR   NOT NULL,

    trunk       VARCHAR   NOT NULL,

    external_ip VARCHAR   NOT NULL,
    internal_ip VARCHAR   NOT NULL,

    created_at  TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at  TIMESTAMP NOT NULL DEFAULT NOW(),

    PRIMARY KEY (id)
);
