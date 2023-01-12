<?php

use Watish\Components\Utils\ENV;

return  [
    "mysql" => [
        "enable" => (bool)ENV::getConfig("Mysql")["DATABASE_ENABLE"],
        "username" => ENV::getConfig("Mysql")["DATABASE_USER"],
        "password" => ENV::getConfig("Mysql")["DATABASE_PASSWORD"],
        "host" => ENV::getConfig("Mysql")["DATABASE_HOST"],
        "port" => (int)ENV::getConfig("Mysql")["DATABASE_PORT"],
        "database" => ENV::getConfig("Mysql")["DATABASE_NAME"],
        'driver' => 'mysql',
        'charset' => ENV::getConfig("Mysql")["DATABASE_CHARSET"],
        'collation' => ENV::getConfig("Mysql")["DATABASE_COLLATION"],
        'prefix' => ENV::getConfig("Mysql")["DATABASE_PREFIX"],
        "max_pool_count" => swoole_cpu_num()*swoole_cpu_num()*6,
        "min_pool_count" => swoole_cpu_num()*swoole_cpu_num()*2
    ],
    "redis" => [
        "enable" => ENV::getConfig("Redis")["REDIS_ENABLE"] == "1",
        "parameters" => [
            "host" => ENV::getConfig("Redis")["REDIS_HOST"],
            "port" => (int)ENV::getConfig("Redis")["REDIS_PORT"],
            "database" => (int)ENV::getConfig("Redis")["REDIS_DATABASE"]
        ],
        "options" => [
            "prefix" => ENV::getConfig("Redis")["REDIS_PREFIX"],
        ],
        "pool_max_count" => swoole_cpu_num()*10,
        "pool_min_count" => swoole_cpu_num()
    ],
];
