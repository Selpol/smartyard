ALTER TABLE sip_servers
    ADD COLUMN external_port INT default 5060;

ALTER TABLE sip_servers
    ADD COLUMN internal_port INT default 5060;