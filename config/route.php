<?php


use Watish\Components\Includes\Route;

function do_register_global_middleware(Route $route):void
{
    /**
    $route->register_global_middleware(CorsMiddleware::class);
     */
}

function do_register_routes(Route $route): void
{
    /**
    $route->register("/",[IndexController::class,'index']);

    $route->register("/test",[HelloController::class,'index']);
    $route->register("/test/db",[HelloController::class,'test_db']);
    $route->register("/test/db2",[HelloController::class,'test_db_2']);

    $route->register("/auth/login",[AuthController::class,'login']);
    $route->register("/auth/register",[AuthController::class,'register']);
    $route->register("/auth/check_token",[AuthController::class,'check_token']);

    $route->register("/home/index",[HomeController::class,'index'],[TokenValid::class]);

    $route->register("/channel/public",[ChannelController::class,'chat_channel']);
     **/
}
