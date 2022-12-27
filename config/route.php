<?php


use Watish\Components\Includes\Route;
use Watish\WatishWEB\Controller\HelloController;
use Watish\WatishWEB\Middleware\CorsMiddleware;

function do_register_global_middleware(Route $route):void
{
    $route->register_global_middleware(CorsMiddleware::class);
}

function do_register_routes(Route $route): void
{
    $route->register('/hello/index',[HelloController::class,'index'],[],[]);
    $route->register('/hello/user/{name}',[HelloController::class,'msg'],[],['GET','POST']);
}
