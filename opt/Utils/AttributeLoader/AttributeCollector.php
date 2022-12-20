<?php

namespace Watish\Components\Utils\AttributeLoader;

class AttributeCollector
{
    private static array $class_attribute_set = [];
    private static array $method_attribute_set = [];
    private static array $property_attribute_set = [];

    public static function scanClass(string $class,string $attribute_class) :array
    {
        if(isset(self::$class_attribute_set[$class][$attribute_class]))
        {
            return self::$class_attribute_set[$class][$attribute_class];
        }
        $reflectionClass = new \ReflectionClass($class);
        $class_attributes = $reflectionClass->getAttributes($attribute_class);
        $result_list = [];
        foreach ($class_attributes as $attribute)
        {
            $result_list[] = self::handle_attribute($attribute);
        }
        self::$class_attribute_set[$class][$attribute_class] = $result_list;
        return $result_list;
    }

    public static function scanProperty(string $class,string $attribute_class):array
    {
        if(isset(self::$property_attribute_set[$class][$attribute_class]))
        {
            return self::$property_attribute_set[$class][$attribute_class];
        }
        $reflectionClass = new \ReflectionClass($class);
        $properties = $reflectionClass->getProperties();
        $result_array = [];
        foreach ($properties as $property)
        {
            $attributes = $property->getAttributes($attribute_class);
            $list_array = [];
            foreach ($attributes as $attribute)
            {
                $list_array["attributes"][] = self::handle_attribute($attribute);
            }
            $list_array["property"] = self::handle_property($property);
            $result_array[] = $list_array;
        }
        self::$property_attribute_set[$class][$attribute_class] = $result_array;
        return $result_array;
    }

    public static function scanMethod(string $class,string $attribute_class):array
    {
        if(isset(self::$method_attribute_set[$class][$attribute_class]))
        {
            return self::$method_attribute_set[$class][$attribute_class];
        }
        $reflectionClass = new \ReflectionClass($class);
        $methods = $reflectionClass->getMethods();
        $result_array = [];
        foreach ($methods as $method)
        {
            $list_array = [];
            $list_array["method"] = self::handle_method($method);
            $attributes = $method->getAttributes($attribute_class);
            foreach ($attributes as $attribute)
            {
                $list_array["attribute"] = self::handle_attribute($attribute);
            }
            $result_array[] = $list_array;
        }
        self::$method_attribute_set[$class][$attribute_class] = $result_array;
        return $result_array;
    }

    private static function handle_attribute(\ReflectionAttribute $class_attribute):array
    {
        return [
            "name" => $class_attribute->getName(),
            "args" => $class_attribute->getArguments(),
            "repeat" => $class_attribute->isRepeated(),
            "target" => $class_attribute->getTarget()
        ];
    }

    private static function handle_property(\ReflectionProperty $property) :array
    {
        return [
            "name" => $property->getName(),
            "type" => $property->getType(),
        ];
    }

    private static function handle_method(\ReflectionMethod $method) :array
    {
        return [
            "name" => $method->getName(),
            "params" => $method->getParameters(),
        ];
    }
}
