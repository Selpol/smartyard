CREATE TABLE schedule
(
    id         SERIAL,

    title      VARCHAR(256) NOT NULL,

    time       VARCHAR(256) NOT NULL,
    script     VARCHAR      NOT NULL,

    status     INT          NOT NULL,

    created_at TIMESTAMP    NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP    NOT NULL DEFAULT NOW(),

    PRIMARY KEY (id)
);
