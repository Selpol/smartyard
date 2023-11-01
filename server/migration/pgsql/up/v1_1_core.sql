-- vars
CREATE TABLE core_vars
(
    var_id    serial primary key,
    var_name  character varying not null,
    var_value character varying
);

CREATE INDEX core_vars_id on core_vars (var_id);
CREATE INDEX core_vars_var_name on core_vars (var_name);

INSERT INTO core_vars (var_name, var_value)
values ('database.version', '0');

-- users
CREATE TABLE core_users
(
    uid           serial primary key,
    login         character varying not null,
    password      character varying not null,
    enabled       integer,
    real_name     character varying,
    e_mail        character varying,
    phone         character varying,
    tg            character varying,
    notification  character varying default 'tgEmail',
    default_route character varying,
    last_login    integer
);

CREATE UNIQUE INDEX core_users_login on core_users (login);

CREATE INDEX core_users_real_name on core_users (real_name);

CREATE UNIQUE INDEX core_users_e_mail on core_users (e_mail);

CREATE INDEX core_users_phone on core_users (phone);

-- admin - admin && user - user
INSERT INTO core_users (uid, login, password, real_name, enabled)
values (0, 'admin', '$2y$10$rU6/RIgJi5ojfuvibG5yHO/Gv5WnclTK6Rc8u.b9mdONHkVMnhJpy', 'admin', 1);
