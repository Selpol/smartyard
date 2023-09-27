CREATE SEQUENCE audit_id_seq INCREMENT BY 1 MINVALUE 1 START 1;

CREATE TABLE audit
(
    id             INT       NOT NULL,

    user_id        INT       NOT NULL,

    auditable_id   VARCHAR   NOT NULL,
    auditable_type VARCHAR   NOT NULL,

    event_ip       VARCHAR   NOT NULL,
    event_type     VARCHAR   NOT NULL,
    event_target   VARCHAR   NOT NULL,
    event_code     VARCHAR   NOT NULL,
    event_message  VARCHAR   NOT NULL,

    created_at     TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at     TIMESTAMP NOT NULL DEFAULT NOW(),

    PRIMARY KEY (id)
);
