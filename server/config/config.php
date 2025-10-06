<?php

use Selpol\Feature\Oauth\Resource\ResourceOauthFeature;

return [
    'language' => env('LANGUAGE', path('var/locale/ru.json')),

    'debug' => boolval(env('DEBUG', '0')),

    'timezone' => 'Europe/Moscow',
    'position' => explode(',', env('POSITION', '0,0')),

    'api' => [
        'frontend' => env('API_FRONTEND', 'http://127.0.0.1/frontend'),
        'asterisk' => env('API_ASTERISK', 'http://127.0.0.1/asterisk'),
        'internal' => env('API_INTERNAL', 'http://127.0.0.1/internal'),
        'private' => env('API_PRIVATE', 'http://127.0.0.1/private'),
        'mobile' => env('API_MOBILE', 'http://127.0.0.1/mobile'),
        'web' => env('API_WEB', 'http://127.0.0.1'),
    ],

    'clickhouse' => [
        'endpoint' => env('CLICKHOUSE_ENDPOINT', 'http://127.0.0.1:8123?database=default'),

        'username' => env('CLICKHOUSE_USERNAME', 'default'),
        'password' => env('CLICKHOUSE_PASSWORD', 'password')
    ],

    'mqtt' => [
        'host' => env('MQTT_HOST', '127.0.0.1'),
        'port' => env('MQTT_PORT', '1883'),

        'username' => env('MQTT_USERNAME', 'username'),
        'password' => env('MQTT_PASSWORD', 'password'),

        'progress' => env('MQTT_PROGRESS', '0') == '1'
    ],

    'internal' => [
        'trust' => explode(',', env('INTERNAL_TRUST', '127.0.0.1/32'))
    ],

    'mobile' => [
        'web_server_base_path' => env('MOBILE_STATIC', 'http://127.0.0.1/static'),
        'time_zone' => env('MOBILE_TIMEZONE', 'Europe/Moscow'),

        'user' => env('MOBILE_USER', '0') == '1',

        'rate_limit' => [
            'enable' => env('MOBILE_RATE_LIMIT_ENABLE', 'true') == 'true',

            'trust' => explode(',', env('MOBILE_TRUST', '127.0.0.1/32')),

            'count' => intval(env('MOBILE_RATE_LIMIT_COUNT', '120')),
            'ttl' => intval(env('MOBILE_RATE_LIMIT_TTL', '30')),

            'null' => env('MOBILE_NULL', '0') == '1',
        ],
    ],

    'db' => [
        'dsn' => 'pgsql:host=' . env('DB_HOST', '127.0.0.1') . ';port=' . intval(env('DB_PORT', '5432')) . ';dbname=' . env('DB_DATABASE', 'rbt'),

        'username' => env('DB_USERNAME', 'rbt'),
        'password' => env('DB_PASSWORD')
    ],

    'redis' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => intval(env('REDIS_PORT', '6379')),

        'user' => env('REDIS_USER'),
        'password' => env('REDIS_USER_PASSWORD'),

        'cache_ttl' => 3600,
        'token_idle_ttl' => 3600,
        'max_allowed_tokens' => 15
    ],

    'mongo' => [
        'uri' => env('MONGO_URI')
    ],

    'amqp' => [
        'host' => env('AMQP_HOST', '127.0.0.1'),
        'port' => intval(env('AMQP_PORT', '5672')),

        'username' => env('AMQP_USERNAME', 'guest'),
        'password' => env('AMQP_PASSWORD', 'guest')
    ],

    'feature' => [
        'role' => [
            'filter_permissions' => explode(',', env('FEATURE_ROLE_FILTER_PERMISSIONS', '*')),
            'default_permissions' => explode(',', env('FEATURE_ROLE_DEFAULT_PERMISSIONS', ''))
        ],

        'frs' => [
            'open_door_timeout' => 10,
        ],

        'plog' => [
            'host' => env('FEATURE_PLOG_HOST'),
            'port' => env('FEATURE_PLOG_PORT'),
            'database' => env('FEATURE_PLOG_DATABASE'),
            'username' => env('FEATURE_PLOG_USERNAME'),
            'password' => env('FEATURE_PLOG_PASSWORD'),

            'ttl_camshot_days' => 30,

            'back_time_shift_video_shot' => intval(env('FEATURE_PLOG_BACK_TIME', 3))
        ],

        'file' => [
            'database' => env('FEATURE_FILES_DB', 'rbt'),
        ],

        'archive' => [
            'dvr_files_ttl' => 259200
        ],

        'geo' => [
            'token' => env('FEATURE_GEOCODER_DADATA'),

            'locations' => json_decode(env('FEATURE_GEOCODER_LOCATIONS', 'null'), true)
        ],

        'push' => [
            'endpoint' => env('FEATURE_ISDN_ENDPOINT'),
            'secret' => env('FEATURE_ISDN_SECRET'),
        ],

        'sip' => [
            'stuns' => explode(',', env('FEATURE_SIP_STUNS', 'stun://stun.l.google.com:19302'))
        ],

        'oauth' => [
            'backend' => env('FEATURE_OAUTH_BACKEND', ResourceOauthFeature::class),

            'public_key' => env('FEATURE_OAUTH_PUBLIC_KEY'),
            'audience' => env('FEATURE_OAUTH_AUDIENCE'),
            'web_api' => env('FEATURE_OAUTH_WEB_API'),
            'secret' => env('FEATURE_OAUTH_SECRET')
        ],

        'dvr' => [
            'token' => env('FEATURE_DVR_TOKEN')
        ],

        'intercom' => [
            'debug' => array_map('intval', array_map('trim', explode(',', env('FEATURE_INTERCOM_DEBUG', ''))))
        ],

        'monitor' => [
            'enable' => env('FEATURE_MONITOR_ENABLE', '0') == '1'
        ]
    ]
];
