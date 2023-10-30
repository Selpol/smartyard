<?php

return [
    'language' => env('LANGUAGE', 'ru'),

    'timezone' => 'Europe/Moscow',

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

    'mqtt' => [
        'trust' => explode(',', env('MQTT_TRUST', '0.0.0.0/0')),

        'host' => env('MQTT_HOST', '127.0.0.1'),
        'port' => env('MQTT_PORT', '1883'),

        'username' => env('MQTT_USERNAME', 'username'),
        'password' => env('MQTT_PASSWORD', 'password')
    ],

    'internal' => [
        'trust' => explode(',', env('INTERNAL_TRUST', '127.0.0.1/32'))
    ],

    'mobile' => [
        'web_server_base_path' => env('MOBILE_STATIC', 'http://127.0.0.1/static'),
        'time_zone' => env('MOBILE_TIMEZONE', 'Europe/Moscow'),

        'trust' => explode(',', env('MOBILE_TRUST', '127.0.0.1/32'))
    ],

    'db' => [
        'dsn' => 'pgsql:host=' . env('DB_HOST', '127.0.0.1') . ';port=' . intval(env('DB_PORT', '5432')) . ';dbname=' . env('DB_DATABASE', 'rbt'),

        'username' => env('DB_USERNAME', 'rbt'),
        'password' => env('DB_PASSWORD')
    ],

    'redis' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => intval(env('REDIS_PORT', '6379')),

        'cache_ttl' => 3600,
        'token_idle_ttl' => 3600,
        'max_allowed_tokens' => 15
    ],

    'amqp' => [
        'host' => env('AMQP_HOST', '127.0.0.1'),
        'port' => intval(env('AMQP_PORT', '5672')),

        'username' => env('AMQP_USERNAME', 'guest'),
        'password' => env('AMQP_PASSWORD', 'guest')
    ],

    'feature' => [
        'role' => [
            'default_permissions' => explode(',', env('FEATURE_ROLE_PERMISSIONS', ''))
        ],

        'frs' => [
            'open_door_timeout' => 10,

            'cron_sync_data_scheduler' => '5min'
        ],

        'plog' => [
            'host' => env('FEATURE_PLOG_HOST'),
            'port' => env('FEATURE_PLOG_PORT'),
            'database' => env('FEATURE_PLOG_DATABASE'),
            'username' => env('FEATURE_PLOG_USERNAME'),
            'password' => env('FEATURE_PLOG_PASSWORD'),

            'max_call_length' => 120,
            'ttl_camshot_days' => 180,

            'back_time_shift_video_shot' => 3
        ],

        'file' => [
            'db' => env('FEATURE_FILES_DB', 'rbt'),
            'uri' => env('FEATURE_FILES_URI')
        ],

        'archive' => [
            'dvr_files_ttl' => 259200
        ],

        'geo' => [
            'token' => env('FEATURE_GEOCODER_DADATA')
        ],

        'push' => [
            'endpoint' => env('FEATURE_ISDN_ENDPOINT'),
            'secret' => env('FEATURE_ISDN_SECRET'),
        ],

        'sip' => [
            'stuns' => explode(',', env('FEATURE_SIP_STUNS', 'stun://stun.l.google.com:19302'))
        ],

        'oauth' => [
            'public_key' => env('FEATURE_OAUTH_PUBLIC_KEY'),
            'audience' => env('FEATURE_OAUTH_AUDIENCE'),
            'web_api' => env('FEATURE_OAUTH_WEB_API'),
            'secret' => env('FEATURE_OAUTH_SECRET')
        ]
    ],

    'syslog_servers' => [
        'beward' => explode(',', env('SYSLOG_SERVERS_BEWARD', 'syslog://127.0.0.1:45450')),
        'beward_ds' => explode(',', env('SYSLOG_SERVERS_BEWARD_DS', 'syslog://127.0.0.1:45451')),
        'is' => explode(',', env('SYSLOG_SERVERS_IS', 'syslog://127.0.0.1:45453')),
        'hikVision' => explode(',', env('SYSLOG_SERVERS_HIKVISION', 'syslog://127.0.0.1:45454')),
    ],

    'ntp_servers' => explode(',', env('NTP_SERVERS', 'ntp://127.0.0.1:123'))
];