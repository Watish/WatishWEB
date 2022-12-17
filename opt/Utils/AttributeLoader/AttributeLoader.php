<?php

namespace Watish\Components\Utils\AttributeLoader;

use ReflectionClass;
use SebastianBergmann\Diff\Exception;
use Watish\Components\Utils\Logger;

class AttributeLoader
{
    private array $classes;

    public function __construct(array $classes)
    {
        $this->classes = $classes;
    }

    public function getAttributes(mixed $attribute) :array
    {
        $resArray = [];
        foreach ($this->classes as $class)
        {
            $resArray[$class] = [
                "attributes" => $this->handle_all($class,$attribute)
            ];
        }
        return $resArray;
    }

    public function getClassAttributes(mixed $attribute) :array
    {
        $resArray = [];
        foreach ($this->classes as $class)
        {
            $attributes = $this->handle_class($class,$attribute);
            $resArray[$class] = [
                "count" => count($attributes),
                "attributes" => $attributes
            ];
        }
        return $resArray;
    }

    public function getMethodAttributes(mixed $attribute) :array
    {
        $resArray = [];
        foreach ($this->classes as $class) {
            $attributes = $this->handle_method($class, $attribute);
            $resArray[$class] = [
                "count" => count($attributes),
                "attributes" => $attributes
            ];
        }
        return $resArray;
    }

    public function getPropertyAttributes(mixed $attribute) :array
    {
        $resArray = [];
        foreach ($this->classes as $class)
        {
            try {
                $reflectionClass = new ReflectionClass($class);
            }catch (Exception $exception)
            {
                Logger::error($exception->getMessage());
                continue;
            }
            $properties = $reflectionClass->getProperties();
            $prompts = [];
            foreach ($properties as $property)
            {
                $attributes = $property->getAttributes(Inject::class);
                $name = $property->getName();
                $type = $property->getType();
                $attr_array = [];
                foreach ($attributes as $attribute){
                    $list_item = [
                        "name" => $attribute->getName(),
                        "args" => $attribute->getArguments()
                    ];
                    $attr_array[] = $list_item;
                }
                $listArray = [
                    "name" => $name,
                    "type" => $type,
                    "attrs" => $attr_array
                ];
                $prompts[] = $listArray;
            }
            $resArray[$class] = $prompts;
        }
        return $resArray;
    }


    private function handle_class(mixed $class,mixed $attribute_class) :array
    {
        try{
            $reflection = new ReflectionClass($class);
        }catch (Exception $exception)
        {
            Logger::debug($exception->getMessage());
            return [];
        }
        $attributes = $reflection->getAttributes($attribute_class);
        $class_attr = [];
        foreach ($attributes as $attribute)
        {
            $class_attr[] = [
                "name" => $attribute->getName(),
                "params" => $attribute->getArguments()
            ];
        }
        return $class_attr;
    }

    private function handle_method(mixed $class,mixed $attribute_class) :array
    {
        $reflection = new ReflectionClass($class);
        $methods = $reflection->getMethods();
        $method_attr = [];
        foreach ($methods as $method)
        {
            $method_name = $method->getName();
            $attributes = $method->getAttributes($attribute_class);
            if(count($attributes) > 0)
            {
                foreach ($attributes as $attribute)
                {
                    $method_attr[] = [
                        "name" => $method_name,
                        "params" => $attribute->getArguments()
                    ];
                }
            }
        }
        return $method_attr;
    }

    private function handle_all(mixed $class,mixed $attribute_class) :array
    {
        $reflection = new ReflectionClass($class);
        $attributes = $reflection->getAttributes($attribute_class);
        $class_attr = [];
        foreach ($attributes as $attribute)
        {
            $class_attr[] = [
                "name" => $attribute->getName(),
                "params" => $attribute->getArguments()
            ];
        }
        $method_attr = [];
        foreach ($reflection->getMethods() as $method)
        {
            $method_name = $method->getName();
            $attributes = $method->getAttributes($attribute_class);
            if(count($attributes) > 0)
            {
                foreach ($attributes as $attribute)
                {
                    $method_attr[] = [
                        "name" => $method_name,
                        "params" => $attribute->getArguments()
                    ];
                }
            }
        }
        return [
            "from_class" => $class_attr,
            "from_methods" => $method_attr
        ];
    }
}
