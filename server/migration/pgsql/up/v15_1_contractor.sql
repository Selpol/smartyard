CREATE TABLE contractor
(
    id         SERIAL,

    title      VARCHAR   NOT NULL,

    flat       INT       NOT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),

    PRIMARY KEY (id)
);
