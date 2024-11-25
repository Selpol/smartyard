CREATE TABLE relays
(
    id         BIGSERIAL NOT NULL,

    title      VARCHAR   NOT NULL,
    url        VARCHAR   NOT NULL,
    credential VARCHAR   NOT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),

    PRIMARY KEY (id)
);

CREATE UNIQUE INDEX relays_unique on relays (url);
