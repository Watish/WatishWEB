<?php

namespace Watish\Components\Utils\Cache;

class ProxyCache implements CacheStaticInterface
{
    private static array $proxy_data = [];
    public static function get(string $key)
    {
        return self::$proxy_data[$key] ?? null;
    }

    public static function set(string $key, mixed $value)
    {
        self::$proxy_data[$key] = $value;
    }

    public static function exists(string $key): bool
    {
        return isset(self::$proxy_data[$key]);
    }

    public static function del(string $key)
    {
        unset(self::$proxy_data[$key]);
    }
}
