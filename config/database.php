<?php

return [
    'default' => "mysql",
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'sistem_api_core'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', 'root'),
            'charset' => env('DB_CHARSET', 'utf8'),
            'collation' => env('DB_COLLATION', 'utf8_unicode_ci'),
            'prefix' => env('DB_PREFIX', ''),
            'timezone' => env('DB_TIMEZONE', '+00:00'),
            'strict' => env('DB_STRICT_MODE', false),
        ],

        'account' => [
            'driver' => 'mysql',
            'host' => env('DB_ACCOUNT_HOST', '127.0.0.1'),
            'port' => env('DB_ACCOUNT_PORT', 3306),
            'database' => env('DB_ACCOUNT_DATABASE', 'account_core'),
            'username' => env('DB_ACCOUNT_USERNAME', 'root'),
            'password' => env('DB_ACCOUNT_PASSWORD', 'root'),
            'charset' => env('DB_ACCOUNT_CHARSET', 'utf8'),
            'collation' => env('DB_ACCOUNT_COLLATION', 'utf8_unicode_ci'),
            'prefix' => env('DB_ACCOUNT_PREFIX', ''),
            'timezone' => env('DB_ACCOUNT_TIMEZONE', '+00:00'),
            'strict' => env('DB_ACCOUNT_STRICT_MODE', false),
        ],

        'siap' => [
            'driver' => 'mysql',
            'host' => env('DB_SIAP_HOST', '127.0.0.1'),
            'port' => env('DB_SIAP_PORT', 3306),
            'database' => env('DB_SIAP_DATABASE', 'siap_core'),
            'username' => env('DB_SIAP_USERNAME', 'root'),
            'password' => env('DB_SIAP_PASSWORD', 'root'),
            'charset' => env('DB_SIAP_CHARSET', 'utf8'),
            'collation' => env('DB_SIAP_COLLATION', 'utf8_unicode_ci'),
            'prefix' => env('DB_SIAP_PREFIX', ''),
            'timezone' => env('DB_SIAP_TIMEZONE', '+00:00'),
            'strict' => env('DB_SIAP_STRICT_MODE', false),
        ],

        'extension' => [
            'driver' => 'mysql',
            'host' => env('DB_EXT_HOST', '127.0.0.1'),
            'port' => env('DB_EXT_PORT', 3306),
            'database' => env('DB_EXT_DATABASE', 'extension_core'),
            'username' => env('DB_EXT_USERNAME', 'root'),
            'password' => env('DB_EXT_PASSWORD', 'root'),
            'charset' => env('DB_EXT_CHARSET', 'utf8'),
            'collation' => env('DB_EXT_COLLATION', 'utf8_unicode_ci'),
            'prefix' => env('DB_EXT_PREFIX', ''),
            'timezone' => env('DB_EXT_TIMEZONE', '+00:00'),
            'strict' => env('DB_EXT_STRICT_MODE', false),
        ],
    ],

    'redis' => [
        'client' => 'predis',
        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => 0,
        ],
    ],

    "migrations" => "migrations",
];
