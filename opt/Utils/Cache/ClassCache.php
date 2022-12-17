<?php

namespace Watish\Components\Utils\Cache;

class ClassCache implements CacheStaticInterface
{
    private static array $class_set = [];

    public static function exists(string $key) :bool
    {
        return isset(self::$class_set[$key]);
    }

    public static function get(string $key)
    {
        return self::$class_set[$key] ?? null;
    }

    public static function set(string $key, mixed $value)
    {
        self::$class_set[$key] = $value;
    }

    public static function del(string $key)
    {
        unset(self::$class_set[$key]);
    }
}
