CREATE TABLE streamer_servers
(
    id         BIGSERIAL NOT NULL,

    title      VARCHAR   NOT NULL,
    url        VARCHAR   NOT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),

    PRIMARY KEY (id)
);

CREATE UNIQUE INDEX streamer_servers_unique on streamer_servers (url);