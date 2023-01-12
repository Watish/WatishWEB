<?php

namespace Watish\Components\Utils;

class ENV
{
    private static array $env = [];

    public static function load(string $filePath): void
    {
        if(file_exists($filePath))
        {
            self::$env = parse_ini_file($filePath,true);
            Logger::info(self::$env);
        }
    }

    public static function getConfig(string $configKey) :array|null
    {
        return self::$env[$configKey] ?? null;
    }

    public static function get(string $key,$default=null) :mixed
    {
        return self::$env[$key] ?? $default;
    }
}
