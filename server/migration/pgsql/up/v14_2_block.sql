-- Основные сервисы для блокировки:
-- 1. Домофония;
-- 2. Видеонаблюдение.

-- Дополнительные статусы сервисов:
-- 1. Звонки;
-- 2. Открытие двери;
-- 3. События;
-- 4. Архив;
-- 5. Распознование лиц;
-- 6. КМС.

CREATE TABLE flat_block
(
    id         BIGSERIAL PRIMARY KEY,

    flat_id    INT       NOT NULL,

    service    INT       NOT NULL, -- 0 - Домофония, 1 - Видеонаблюдение, 2 - Звонки, 3 - Открытие двери, 4 - События, 5 - Архив, 6 - Распознование лиц, 7 - КМС

    status     INT       NOT NULL, -- 1 - Блокировка администратором, 2 - Блокировка биллингом

    cause      VARCHAR   NULL,
    comment    VARCHAR   NULL,

    created_at TIMESTAMP NOT NULL DEFAULT now(),
    updated_at TIMESTAMP NOT NULL DEFAULT now(),

    FOREIGN KEY (flat_id) REFERENCES houses_flats (house_flat_id)
);

CREATE TABLE subscriber_block
(
    id            BIGSERIAL PRIMARY KEY,

    subscriber_id INT       NOT NULL,

    service       INT       NOT NULL, -- 0 - Домофония, 1 - Видеонаблюдение, 2 - Звонки, 3 - Открытие двери, 4 - События, 5 - Архив, 6 - Распознование лиц

    status        INT       NOT NULL, -- 1 - Блокировка администратором, 2 - Блокировка биллингом

    cause         VARCHAR   NULL,
    comment       VARCHAR   NULL,

    created_at    TIMESTAMP NOT NULL DEFAULT now(),
    updated_at    TIMESTAMP NOT NULL DEFAULT now(),

    FOREIGN KEY (subscriber_id) REFERENCES houses_subscribers_mobile (house_subscriber_id)
);
