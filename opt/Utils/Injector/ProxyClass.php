<?php

namespace Watish\Components\Utils\Injector;

use Watish\Components\Attribute\Aspect;
use Watish\Components\Attribute\Async;
use Watish\Components\Constructor\AsyncTaskConstructor;
use Watish\Components\Utils\Aspect\AspectContainer;
use Watish\Components\Utils\Cache\ProxyCache;
use Watish\Components\Utils\Logger;

class ProxyClass
{
    private mixed $class;
    private \ReflectionClass $reflectionClass;
    private array $proxy_data = [];

    public function __construct(mixed $class)
    {
        $this->class = $class;
        try{
            $reflectionClass = new \ReflectionClass($class);
        }catch (\Exception $exception)
        {
            Logger::exception($exception);
            return;
        }

        $class_name = $reflectionClass->getName();
        if(ProxyCache::exists($class_name))
        {
            $this->proxy_data = ProxyCache::get($class_name);
            return;
        }

        $methods = $reflectionClass->getMethods();

        $proxy_data = [
            "aspect" => [],
            "async" => []
        ];
        foreach ($methods as $method)
        {
            $method_name = $method->getName();
            $method_attributes = $method->getAttributes(Aspect::class);
            if(count($method_attributes) <= 0)
            {
                continue;
            }
            $listArray = [];
            foreach ($method_attributes as $method_attribute)
            {
                $listArray[] = $method_attribute->getArguments()[0];
            }
            $proxy_data["aspect"][$method_name] = $listArray;
        }
        foreach ($methods as $method)
        {
            $method_name = $method->getName();
            $method_attributes = $method->getAttributes(Async::class);
            if(count($method_attributes) <= 0)
            {
                continue;
            }
            $listArray = [];
            foreach ($method_attributes as $method_attribute)
            {
                if(isset($method_attribute->getArguments()[0]))
                {
                    $listArray[] = $method_attribute->getArguments()[0];
                }
            }
            $proxy_data["async"][$method_name] = $listArray;
        }
        $this->proxy_data = $proxy_data;
        ProxyCache::set($class_name,$proxy_data);
        Logger::debug("Proxy Class:{$class_name}","ProxyClass");
    }

    public function __call(string $name, array $arguments)
    {
        Logger::debug("Be Called $name","ProxyClass");
        //Aspect
        if(isset($this->proxy_data["aspect"][$name]))
        {
            foreach ($this->proxy_data["aspect"][$name] as $list_aspect_class)
            {
                try {
                    $result = (new $list_aspect_class)->handle($arguments);
                    if(!AspectContainer::getStatus())
                    {
                        return $result;
                    }
                }catch (\Exception $exception)
                {
                    Logger::exception($exception);
                }
            }
        }
        //Async
        if(isset($this->proxy_data["async"][$name]))
        {
            Logger::debug("Async Call Method: $name","ProxyClass");
            AsyncTaskConstructor::make(function () use ($name,$arguments){
                call_user_func_array([$this->class,$name],$arguments);
            });
            return 1;
        }
        //Method
        try{
            $result = call_user_func_array([$this->class,$name],$arguments);
        }catch (\Exception $exception)
        {
            Logger::exception($exception);
        }
        return $result;
    }
}
