<?php

return [
    'language' => env('LANGUAGE', 'ru'),

    'api' => [
        'frontend' => env('API_FRONTEND', 'http://127.0.0.1/frontend'),
        'asterisk' => env('API_ASTERISK', 'http://127.0.0.1/asterisk'),
        'internal' => env('API_INTERNAL', 'http://127.0.0.1/internal'),
        'private' => env('API_PRIVATE', 'http://127.0.0.1/private'),
        'mobile' => env('API_MOBILE', 'http://127.0.0.1/mobile')
    ],

    'asterisk' => [
        'trust' => explode(',', env('ASTERISK_TRUST', '127.0.0.1/32'))
    ],

    'internal' => [
        'logger' => env('INTERNAL_LOGGER', false),

        'trust' => explode(',', env('INTERNAL_TRUST', '127.0.0.1/32'))
    ],

    'mobile' => [
        'web_server_base_path' => env('MOBILE_STATIC', 'http://127.0.0.1/static'),
        'time_zone' => env('MOBILE_TIMEZONE', 'Europe/Moscow'),

        'trust' => explode(',', env('MOBILE_TRUST', '127.0.0.1/32'))
    ],

    'db' => [
        'dsn' => 'pgsql:host=' . env('DB_HOST', '127.0.0.1') . ';port=' . env('DB_PORT', 5432) . ';dbname=' . env('DB_DATABASE', 'rbt'),

        'username' => env('DB_USERNAME', 'rbt'),
        'password' => env('DB_PASSWORD')
    ],

    'redis' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', 6379),

        'cache_ttl' => 3600,
        'token_idle_ttl' => 3600,
        'max_allowed_tokens' => 15
    ],

    'amqp' => [
        'host' => env('AMQP_HOST', '127.0.0.1'),
        'port' => env('AMQP_PORT', 5672),

        'username' => env('AMQP_USERNAME', 'guest'),
        'password' => env('AMQP_PASSWORD', 'guest')
    ],

    'backends' => [
        'authentication' => ['backend' => 'internal'],
        'authorization' => ['backend' => 'allow'],
        'users' => ['backend' => 'internal'],

        'geocoder' => [
            'backend' => 'dadata',

            'token' => env('BACKEND_GEOCODER_DADATA')
        ],

        'files' => [
            'backend' => 'mongo',

            'db' => env('BACKEND_FILES_DB', 'rbt'),
            'uri' => env('BACKEND_FILES_URI')
        ],

        'addresses' => ['backend' => 'internal'],
        'households' => ['backend' => 'internal'],
        'cameras' => ['backend' => 'internal'],

        'isdn' => [
            'backend' => env('BACKEND_ISDN_BACKEND'),

            'endpoint' => env('BACKEND_ISDN_ENDPOINT'),
            'secret' => env('BACKEND_ISDN_SECRET'),

            'confirm_method' => 'outgoingCall'
        ],

        'inbox' => ['backend' => 'clickhouse'],

        'plog' => [
            'backend' => 'clickhouse',

            'host' => env('BACKEND_PLOG_HOST'),
            'port' => env('BACKEND_PLOG_PORT'),

            'database' => env('BACKEND_PLOG_DATABASE'),
            'username' => env('BACKEND_PLOG_USERNAME'),
            'password' => env('BACKEND_PLOG_PASSWORD'),

            'time_shift' => 60,
            'max_call_length' => 120,
            'ttl_temp_record' => 86400,
            'ttl_camshot_days' => 180,

            'back_time_shift_video_shot' => 3,

            'cron_process_events_scheduler' => 'minutely'
        ],

        'configs' => ['backend' => 'internal'],

        'dvr' => [
            'backend' => 'internal',

            /**
             * @example BACKEND_DVR_SERVERS=[{"title": "DVR", "type": "flussonic", "url": "https://flussonic:8443", "token": "..."}]
             */
            'servers' => json_decode(env('BACKEND_DVR_SERVERS', '[]'), true)
        ],

        'dvr_exports' => [
            'backend' => 'mongo',

            'dvr_files_ttl' => 259200
        ],

        'sip' => [
            'backend' => 'internal',

            /**
             * @example BACKEND_SIP_SERVERS=[{"title": "SIP", "type": "asterisk", "trunk": "first", "ip": "127.0.0.1"}]
             */
            'servers' => json_decode(env('BACKEND_SIP_SERVERS', '[]'), true),

            'stuns' => explode(',', env('BACKEND_SIP_STUNS', 'stun://stun.l.google.com:19302'))
        ],

        'frs' => [
            'backend' => 'internal',

            /**
             * @example BACKEND_FRS_SERVERS=[{"title": "FRS", "url": "http://127.0.0.1:9051"}]
             */
            'servers' => json_decode(env('BACKEND_FRS_SERVERS', '[]'), true),

            'open_door_timeout' => 10,

            'cron_sync_data_scheduler' => '5min'
        ],

        'oauth' => [
            'backend' => 'internal',

            'public_key' => env('BACKEND_OAUTH_PUBLIC_KEY'),
            'audience' => env('BACKEND_OAUTH_AUDIENCE'),
            'web_api' => env('BACKEND_OAUTH_WEB_API'),
            'secret' => env('BACKEND_OAUTH_SECRET')
        ],

        'task' => ['backend' => 'internal']
    ],

    'syslog_servers' => [
        'is' => explode(',', env('SYSLOG_SERVERS_IS', 'syslog://127.0.0.1:45453'))
    ],

    'ntp_servers' => explode(',', env('NTP_SERVERS', 'ntp://127.0.0.1:123'))
];