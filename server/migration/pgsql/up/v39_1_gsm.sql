CREATE TABLE houses_gsm_subscribers (
    id BIGSERIAL PRIMARY KEY,
    house_subscriber_id INT NOT NULL,
    house_domophone_id INT NOT NULL,
    count INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
    FOREIGN KEY (house_subscriber_id) REFERENCES houses_subscribers_mobile (house_subscriber_id),
    FOREIGN KEY (house_domophone_id) REFERENCES houses_domophones (house_domophone_id)
);