<?php

return  [
    "mysql" => [
        "enable" => true,
        "username" => "debian-sys-maint",
        "password" => "S17pnBhHeLFsDZih",
        "host" => "127.0.0.1",
        "port" => 3306,
        "database" => "little_chat",
        'driver' => 'mysql',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'prefix' => '',
        "max_pool_count" => swoole_cpu_num()*8,
        "min_pool_count" => swoole_cpu_num()*4
    ],
    "redis" => [
        "enable" => true,
        "parameters" => [
            "host" => "127.0.0.1",
            "port" => 6379,
            "database" => 0
        ],
        "options" => [
            "prefix" => "",
        ],
        "pool_max_count" => swoole_cpu_num()*10,
        "pool_min_count" => swoole_cpu_num()
    ],
];
