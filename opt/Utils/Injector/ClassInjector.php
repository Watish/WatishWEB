<?php

namespace Watish\Components\Utils\Injector;

use SebastianBergmann\Diff\Exception;
use Watish\Components\Attribute\Aspect;
use Watish\Components\Attribute\Inject;
use Watish\Components\Constructor\ClassLoaderConstructor;
use Watish\Components\Utils\Cache\ClassCache;
use Watish\Components\Utils\Logger;

class ClassInjector
{

    public static array $deep_class_pool = [];
    public static array $deep_class_count = [];

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

        $methods = $reflectionClass->getMethods();
        $proxy = false;
        foreach ($methods as $method)
        {
            $methodAttributes = $method->getAttributes(Aspect::class);
            if(count($methodAttributes) > 0)
            {
                $proxy = true;
                break;
            }
        }
        if($proxy)
        {
            //Need to proxy aspect
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
            self::$deep_class_pool[$className] = new $className();
        }else{
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
        }catch (Exception $exception)
        {
            Logger::exception($exception);
            return null;
        }

        $obj = new $className();

        //Put Dependency A First
        self::$deep_class_pool[$className] = $obj;

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
        ClassCache::set($className,$obj);
        return $obj;
    }
}
