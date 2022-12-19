<?php

namespace Watish\Components\Utils\Injector;

use SebastianBergmann\Diff\Exception;
use Watish\Components\Attribute\Aspect;
use Watish\Components\Attribute\Async;
use Watish\Components\Attribute\Inject;
use Watish\Components\Constructor\ClassLoaderConstructor;
use Watish\Components\Utils\Cache\ClassCache;
use Watish\Components\Utils\Logger;

class ClassInjector
{

    public static array $deep_class_pool = [];
    public static array $deep_class_count = [];

    private static array $proxy_class_pool = [];

    public static function init(): void
    {
        //Init class to Cache
        foreach (ClassLoaderConstructor::getClassLoaderList() as $classLoader)
        {
            foreach ($classLoader->getClasses() as $class)
            {
                self::getInjectedInstance($class);
            }
        }
    }

    public static function getInjectedInstance(mixed $className)
    {
        try {
            $reflectionClass = new \ReflectionClass($className);
            $class_name = $reflectionClass->getName();
        }catch (Exception $exception)
        {
            Logger::exception($exception);
            return null;
        }

        if(ClassCache::exists($class_name))
        {
            Logger::debug("Hit Cache : {$class_name}","Injector");
            $obj = ClassCache::get($class_name);
            if(!is_null($obj))
            {
                return $obj;
            }
        }

        $properties = $reflectionClass->getProperties();
        $obj = new ($className)();
        foreach ($properties as $property)
        {
            $attributes = $property->getAttributes(Inject::class);
            $property_name = $property->getName();
            if(count($attributes) <= 0)
            {
                //Not need to inject
                continue;
            }

            $inject_class_name = $attributes[0]->getArguments()[0];
            $property->setValue($obj,self::deep_inject_to_instance($inject_class_name));
            Logger::debug("{$class_name}:{$property_name} Injected by {$inject_class_name}","Injector");
        }

        $proxy = self::need_proxy($reflectionClass->getMethods(),[
            Aspect::class,
            Async::class
        ]);

        if($proxy)
        {
            $obj = new ProxyClass($obj);
        }

        //Cache Class
        ClassCache::set($class_name,$obj);
        return $obj;
    }

    private static function deep_inject_to_instance(string $className)
    {
        if(!isset(self::$deep_class_count[$className]))
        {
            self::$deep_class_count[$className] = 1;
        }
        else{
            self::$deep_class_count[$className] ++;
            if(self::$deep_class_count[$className] >= 2)
            {
                Logger::debug("Deep Inject $className","Injector");
                $obj = self::$deep_class_pool[$className];
                unset(self::$deep_class_pool[$className]);
                unset(self::$deep_class_count[$className]);
                return $obj;
            }
        }

        try{
            $reflectionClass = new \ReflectionClass($className);
            $proxy = self::need_proxy($reflectionClass->getMethods(),[
                Aspect::class,
                Async::class
            ]);
        }catch (Exception $exception)
        {
            Logger::exception($exception);
            return null;
        }

        $obj = new $className();

        //Inject First
        $properties = $reflectionClass->getProperties();
        foreach ($properties as $property)
        {
            $attributes = $property->getAttributes(Inject::class);
            if(count($attributes) <= 0)
            {
                //Not need to inject
                continue;
            }
            $inject_class_name = $attributes[0]->getArguments()[0];
            $property->setValue($obj,self::deep_inject_to_instance($inject_class_name));
        }

        //Then Proxy
        if($proxy)
        {
            $obj = new ProxyClass($obj);
        }
        //Put class finally
        self::$deep_class_pool[$className] = $obj;

        ClassCache::set($className,$obj);
        return $obj;
    }

    /**
     * @param \ReflectionMethod[] $methods
     * @param \ReflectionAttribute[] $attributeClassList
     * @return bool
     */
    private static function need_proxy(array $methods,array $attributeClassList) :bool
    {
        foreach ($methods as $method)
        {

            foreach ($attributeClassList as $attributeClass)
            {
                $attributes = $method->getAttributes($attributeClass);
                if(count($attributes) > 0)
                {
                    return true;
                }
            }
        }
        return false;
    }
}
