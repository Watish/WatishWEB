<?php

namespace Watish\Components\Utils\Injector;

use ReflectionAttribute;
use ReflectionMethod;
use ReflectionProperty;
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
        $attributesList = self::property_attribute_list();
        foreach ($properties as $property)
        {
            $property_name = $property->getName();
            $attribute_class = $attributesList[0];
            $attributes = self::need_inject_property($property,$attribute_class);
            if(is_null($attributes)) {
                //Not need to inject
                continue;
            }
            $inject_class_name = $attributes[0]->getArguments()[0];
            $property->setValue($obj,self::deep_inject_to_instance($inject_class_name));
            Logger::debug("{$class_name}:{$property_name} Injected by {$inject_class_name}","Injector");

        }

        $proxy = self::need_proxy($reflectionClass->getMethods(),self::method_attribute_list());

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
            $proxy = self::need_proxy($reflectionClass->getMethods(),self::method_attribute_list());
        }catch (Exception $exception)
        {
            Logger::exception($exception);
            return null;
        }

        $obj = new $className();

        //Inject First
        $properties = $reflectionClass->getProperties();
        $property_attribute_list = self::property_attribute_list();
        foreach ($properties as $property)
        {
            $attribute_class = $property_attribute_list[0];
            $attributes = self::need_inject_property($property,$attribute_class);
            if(is_null($attributes))
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
     * @param ReflectionMethod[] $methods
     * @param string[] $attributeClassList
     * @return bool
     */
    private static function need_proxy(array $methods,array $attributeClassList) :bool
    {
        foreach ($methods as $method)
        {
            $params = $method->getParameters();
            Logger::debug($params,"Params_Proxy");
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

    /**
     * @param ReflectionProperty $property
     * @param string $attribute
     * @return null|ReflectionAttribute[]
     */
    private static function need_inject_property(ReflectionProperty $property, string $attribute) :null|array
    {
        $attributes = $property->getAttributes($attribute);
        if(count($attributes) > 0)
        {
            return $attributes;
        }
        return null;
    }

    private static function property_attribute_list() :array
    {
        return [
            Inject::class
        ];
    }

    private static function method_attribute_list() :array
    {
        return [
            Aspect::class,
            Async::class
        ];
    }
}
