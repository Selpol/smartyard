CREATE SEQUENCE dvr_servers_id_seq INCREMENT BY 1 MINVALUE 1 START 1;

CREATE TABLE dvr_servers
(
    id         INT       NOT NULL,

    title      VARCHAR   NOT NULL,
    type       VARCHAR   NOT NULL,

    url        VARCHAR   NOT NULL,

    token      VARCHAR   NOT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),

    PRIMARY KEY (id)
);

CREATE SEQUENCE frs_servers_id_seq INCREMENT BY 1 MINVALUE 1 START 1;

CREATE TABLE frs_servers
(
    id         INT       NOT NULL,

    title      VARCHAR   NOT NULL,

    url        VARCHAR   NOT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),

    PRIMARY KEY (id)
);
