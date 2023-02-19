<?php

use Watish\Components\Utils\ENV;

return  [
    "debug_mode" => (bool)ENV::getConfig("App")["DEBUG_MODE"],
    "worker_num" => swoole_cpu_num(),
    "listen_host" => ENV::getConfig("Server")["LISTEN_HOST"],
    "listen_port" => (int)ENV::getConfig("Server")["LISTEN_PORT"],
    "timezone" => ENV::getConfig("Server")["TIMEZONE"],
    "cache_path" => ENV::getConfig("Server")["TEMP_PATH"].'/'.md5(ENV::getConfig("App")["APP_NAME"]),
    //Autoload Class and injector will inject classes loaded
    "class_loader" => [
        "controller" => [
            "dir" => "/src/Controller/",
            "namespace" => "Watish\WatishWEB\Controller",
            "deep" => true,
        ],
        "aspect" => [
            "dir" => "/src/Aspect/",
            "namespace" => "Watish\WatishWEB\Aspect",
            "deep" => true,
        ],
        "middleware" => [
            "dir" => "/src/Middleware/",
            "namespace" => "Watish\WatishWEB\Middleware",
            "deep" => true,
        ],
        "service" => [
            "dir" => "/src/Service/",
            "namespace" => "Watish\WatishWEB\Service",
            "deep" => true
        ],
        "crontab" => [
            "dir" => "/src/Task/",
            "namespace" => "Watish\WatishWEB\Task",
            "deep" => true,
        ],
        "command" => [
            "dir" => "/src/Command/",
            "namespace" => "Watish\WatishWEB\Command",
            "deep" => true,
        ],
        "process" => [
            "dir" => "/src/Process/",
            "namespace" => "Watish\WatishWEB\Process",
            "deep" => true,
        ],
        "event"  => [
            "dir" => "/src/Event/",
            "namespace" => "Watish\WatishWEB\Event",
            "deep" => true,
        ]
    ],

    // When setting true, all controllers
    // in directory /src/Controller/ will be scanned
    // and parse its Prefix,Path,Middleware attributes to register route
    "register_route_auto" => true,
    "register_process_auto" => true
];
