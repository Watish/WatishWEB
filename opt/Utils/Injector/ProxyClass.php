<?php

namespace Watish\Components\Utils\Injector;

use Watish\Components\Attribute\Aspect;
use Watish\Components\Attribute\Inject;
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

        $proxy_data = [];
        foreach ($methods as $method)
        {
            $method_name = $method->getName();
            $method_return_type = $method->getReturnType();
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
            $proxy_data[$method_name] = $listArray;
        }
        $this->proxy_data = $proxy_data;
        ProxyCache::set($class_name,$proxy_data);
        Logger::debug("Proxy Class:{$class_name}","ProxyClass");
    }

    public function __call(string $name, array $arguments)
    {
        Logger::debug("Be Called $name","ProxyClass");
        if(!isset($this->proxy_data[$name]))
        {
            try{
                call_user_func_array([$this->class,$name],$arguments);
            }catch (\Exception $exception)
            {
                Logger::exception($exception);
            }
        }
        foreach ($this->proxy_data[$name] as $list_aspect_class)
        {
            $result = (new $list_aspect_class)->handle($arguments);
            if(!AspectContainer::getStatus())
            {
                return $result;
            }
        }
        return call_user_func_array([$this->class,$name],$arguments);
    }
}
