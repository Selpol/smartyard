ALTER TABLE houses_flats
    ADD COLUMN manual_block INTEGER;

ALTER TABLE houses_flats
    ADD COLUMN auto_block INTEGER;

ALTER TABLE houses_flats
    ADD COLUMN admin_block INTEGER;

ALTER TABLE houses_flats
    ADD COLUMN description_block VARCHAR;

ALTER TABLE houses_subscribers_mobile
    ADD COLUMN manual_block INTEGER;

ALTER TABLE houses_subscribers_mobile
    ADD COLUMN admin_block INTEGER;

ALTER TABLE houses_subscribers_mobile
    ADD COLUMN description_block VARCHAR;