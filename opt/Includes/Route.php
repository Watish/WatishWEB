<?php

namespace Watish\Components\Includes;

use Watish\Components\Attribute\GlobalMiddleware;
use Watish\Components\Attribute\Middleware;
use Watish\Components\Attribute\Path;
use Watish\Components\Attribute\Prefix;
use Watish\Components\Constructor\ClassLoaderConstructor;
use Watish\Components\Utils\AttributeLoader\AttributeLoader;
use Watish\Components\Utils\Logger;
use Watish\Components\Utils\Table;

class Route
{
    /**
     * @var array
     * Set : "path" => [Controller:class,'Method']
     */
    private array $routes;
    private array $global_middlewares;
    private array $routes_path_set;

    public function __construct()
    {
        $this->routes = [];
        $this->global_middlewares = [];
        $this->routes_path_set = [];
    }

    public function auto_register_route() :void
    {
        //Load Controller
        $controllerLoader = ClassLoaderConstructor::getClassLoader("controller");
        $classes = $controllerLoader->getClasses();
        $attributesLoader = new AttributeLoader($classes);
        $PrefixAttr = $attributesLoader->getClassAttributes(Prefix::class);
        $PathAttr = $attributesLoader->getMethodAttributes(Path::class);
        $MiddlewareAttr = $attributesLoader->getAttributes(Middleware::class);

        $classSet = [];
        foreach ($PrefixAttr as $class => $list_prefix_attr)
        {
            $has_prefix = false;
            $prefix = "";
            $attributes = $list_prefix_attr["attributes"];
            if($list_prefix_attr["count"] > 0)
            {
                $params = $attributes[0]["params"];
                $prefix = $params[0];
                $has_prefix = true;
            }
            $classSet[$class] = [
                "has_prefix" => $has_prefix,
                "prefix" => $prefix,
                "routes" => []
            ];
        }
        foreach ($MiddlewareAttr as $class => $list_middleware_attr)
        {
            $attributes = $list_middleware_attr["attributes"];
            $from_class = $attributes["from_class"];
            $from_methods = $attributes["from_methods"];
            if(count($from_class)>0)
            {
                $middleware_list = $from_class[0]["params"][0];
                $classSet[$class]["middlewares"] = $middleware_list;
            }
            if(count($from_methods)>0)
            {
                foreach ($from_methods as $from_method)
                {
                    $method_name = $from_method["name"];
                    $method_middleware_list = $from_method["params"][0];
                    $classSet[$class]["method_middleware"][$method_name] = $method_middleware_list;
                }
            }
        }
        $route_list = [];
        foreach ($PathAttr as $class => $list_path_attr)
        {
            $count = $list_path_attr["count"];
            if($count<=0)
            {
                continue;
            }
            $attributes = $list_path_attr["attributes"];
            foreach ($attributes as $method_arr)
            {
                $method_name = $method_arr["name"];
                $real_path = $method_arr["params"][0];
                if($classSet[$class]["has_prefix"])
                {
                    $prefix = $classSet[$class]["prefix"];
                    $real_path = $prefix.$real_path;
                }
                $route_list[] = [
                    "class" => $class,
                    "method" => $method_name,
                    "path" => $real_path
                ];
            }
        }
        $table_array = [];
        foreach ($route_list as $item)
        {
            $class = $item["class"];
            $method = $item["method"];
            $path = $item["path"];
            $class_middlewares = [];
            $method_middlewares = [];
            if(isset($classSet[$class]["middlewares"]))
            {
                $class_middlewares = $classSet[$class]["middlewares"];
            }
            if(isset($classSet[$class]["method_middleware"][$method]))
            {
                $method_middlewares = $classSet[$class]["method_middleware"][$method];
            }
            $middlewares_set = [];
            foreach ($class_middlewares as $class_middleware)
            {
                if(!isset($middlewares_set[$class_middleware]))
                {
                    $middlewares_set[$class_middleware] = null;
                }
            }
            foreach ($method_middlewares as $method_middleware)
            {
                if(!isset($middlewares_set[$method_middleware]))
                {
                    $middlewares_set[$method_middleware] = null;
                }
            }
            $middlewares = array_keys($middlewares_set);
            $table_array[] = [
                "path" =>  $path,
                "class" => $class,
                "method" => $method,
                "middlewares" => implode(",",$middlewares)
            ];
            $this->register($path,[new $class(),$method],$middlewares);
        }
        if(Table::get("server_config")["debug_mode"])
        {
            Logger::info("Autoload Roue Path Table","Route");
            Logger::table($table_array);
        }
    }

    public function auto_register_global_middleware() :void
    {
        $classLoader = ClassLoaderConstructor::getClassLoader("middleware");
        $attribute_loader = new AttributeLoader($classLoader->getClasses());
        $attributes = $attribute_loader->getClassAttributes(GlobalMiddleware::class);
        foreach ($attributes as $middleware_class => $item)
        {
            if($item["count"]<=0)
            {
                continue;
            }
            $this->register_global_middleware($middleware_class);
        }
    }

    public function get_routes() :array
    {
        arsort($this->routes_path_set);
        $resArray = [];
        foreach ($this->routes_path_set as $path => $path_length)
        {
            $resArray[$path] = $this->routes[$path];
        }
        return $resArray;
    }

    public function path_exists(string $path) :bool
    {
        return isset($this->routes[$path]);
    }

    public function get_path_closure(string $path):array
    {
        return $this->routes[$path];
    }

    /**
     * @param string $path
     * @param array $closure_array
     * @return void
     */
    public function register(string $path,array $closure_array,array $before_middlewares = []):void
    {
        $path_length = strlen($path);
        if(isset($this->routes[$path]))
        {
            Logger::error("Path Duplicated:{$path}","Route");
            return;
        }
        $this->routes[$path] = [
            "callback" => $closure_array,
            "before_middlewares" => $before_middlewares
        ];
        $this->routes_path_set[$path] = $path_length;
    }

    public function register_global_middleware(mixed $class_name) :void
    {
        $this->global_middlewares[] = $class_name;
    }

    public function get_global_middlewares():array
    {
        return $this->global_middlewares;
    }
}
