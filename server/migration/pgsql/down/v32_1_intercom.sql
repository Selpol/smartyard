ALTER TABLE
    houses_domophones
ADD
    COLUMN enabled integer not null default 1,
ADD
    COLUMN sos_number VARCHAR,
ADD
    COLUMN dtmf character varying not null default '1'
ADD
    COLUMN nat integer;