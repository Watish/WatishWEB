<?php

namespace Watish\Components\Utils\Lock;

use Swoole\Coroutine;
use Watish\Components\Struct\Hash\Hash;
use Watish\Components\Utils\Logger;

class MultiLock
{
    private static array $lockSet = [];
    private static array $waitHash = [];

    public static function lock(string $name="default") :void
    {
        $cid = Coroutine::getCid();
        if(!isset(self::$lockSet[$name]))
        {
            self::$lockSet[$name] = false;
        }
        if(self::$lockSet[$name])
        {
            self::$waitHash[$name][] = $cid;
            Coroutine::yield();
        }
        self::$lockSet[$name] = true;
    }

    public static function unlock(string $name="default") :void
    {
        $cid = Coroutine::getCid();
        if(!isset(self::$lockSet[$name]))
        {
            self::$lockSet[$name] = false;
        }
        self::$lockSet[$name] = false;
        if(!isset(self::$waitHash[$name]))
        {
            self::$waitHash[$name] = [];
        }
        if(count(self::$waitHash[$name]) > 0)
        {
            $cid = array_pop(self::$waitHash[$name]);
            Coroutine::resume($cid);
        }
    }
}
