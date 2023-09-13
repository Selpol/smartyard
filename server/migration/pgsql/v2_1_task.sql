CREATE SEQUENCE task_id_seq INCREMENT BY 1 MINVALUE 1 START 1;

CREATE TABLE task
(
    id         INT       NOT NULL,

    data       TEXT      NOT NULL,

    title      TEXT      NOT NULL,
    message    TEXT      NOT NULL,

    status     SMALLINT  NOT NULL DEFAULT 0,

    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),

    PRIMARY KEY (id)
);
