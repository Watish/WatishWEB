<?php

namespace Watish\Components\Utils\Cache;

interface CacheStaticInterface
{
    public static function get(string $key);

    public static function set(string $key,mixed $value);

    public static function exists(string $key):bool;

    public static function del(string $key);
}
