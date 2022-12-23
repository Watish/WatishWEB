<?php

return  [
    "debug_mode" => true,
    "worker_num" => swoole_cpu_num(),
    "listen_host" => "0.0.0.0",
    "listen_port" => 9502,
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
        ]
    ],

    // When setting true, all controllers
    // in directory /src/Controller/ will be scanned
    // and parse its Prefix,Path,Middleware attributes to register route
    "register_route_auto" => true
];
