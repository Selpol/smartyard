CREATE TABLE prod.syslog
(
    `date` UInt32,
    `ip`   IPv4,
    `unit` String,
    `msg`  String,
    INDEX  syslog_ip ip TYPE set(100) GRANULARITY 1024,
    INDEX  syslog_unit unit TYPE set(100) GRANULARITY 1024
) ENGINE = MergeTree PARTITION BY (toYYYYMMDD(FROM_UNIXTIME(date)), unit) ORDER BY date TTL FROM_UNIXTIME(date) + toIntervalDay(31) SETTINGS index_granularity = 8192;

CREATE TABLE prod.plog
(
    `date`       UInt32,
    `event_uuid` UUID,
    `hidden`     Int8,
    `image_uuid` UUID,
    `flat_id`    Int32,
    `domophone`  JSON,
    `event`      Int8,
    `opened`     Int8,
    `face`       JSON,
    `rfid`       String,
    `code`       String,
    `phones`     JSON,
    `preview`    Int8,
    INDEX        plog_date date TYPE set(100) GRANULARITY 1024,
    INDEX        plog_event_uuid event_uuid TYPE set(100) GRANULARITY 1024,
    INDEX        plog_hidden hidden TYPE set(100) GRANULARITY 1024,
    INDEX        plog_flat_id flat_id TYPE set(100) GRANULARITY 1024
) ENGINE = ReplacingMergeTree() PARTITION BY toYYYYMMDD(FROM_UNIXTIME(date)) ORDER BY date TTL FROM_UNIXTIME(date) + toIntervalMonth(6) SETTINGS index_granularity = 1024;

CREATE TABLE prod.motion
(
    `ip`    IPv4,
    `start` UInt32,
    `end`   UInt32,
    INDEX   motion_ip ip TYPE set(100) GRANULARITY 1024,
    INDEX   motion_start start TYPE set(100) GRANULARITY 1024,
    INDEX   motion_end end TYPE set(100) GRANULARITY 1024,
) ENGINE = ReplacingMergeTree() PARTITION BY toYYYYMMDD(FROM_UNIXTIME(start)) ORDER BY start TTL FROM_UNIXTIME(start) + toIntervalMonth(6) SETTINGS index_granularity = 1024;
