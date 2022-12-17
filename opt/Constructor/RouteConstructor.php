<?php

namespace Watish\Components\Constructor;

use Watish\Components\Includes\Route;
use Watish\Components\Utils\Table;

class RouteConstructor
{
    private static Route $route;

    public static function init(): void
    {
        $server_config = Table::get("server_config");
        $route = new Route();
        require_once BASE_DIR . '/config/route.php';
        do_register_global_middleware($route);
        if($server_config["register_route_auto"])
        {
            $route->auto_register_route();
            $route->auto_register_global_middleware();
        }else{
            do_register_routes($route);
        }
        self::$route = $route;
    }

    /**
     * @return Route
     */
    public static function getRoute(): Route
    {
        return self::$route;
    }
}
