<?php

namespace Watish\Components\Utils\Aspect;

use Swoole\Coroutine;

class AspectContainer
{

    private static array $set = [];

    public static function skip() :void
    {
        $cid = Coroutine::getuid();
        self::$set[$cid] = 0;
    }

    public static function continue():void
    {
        $cid = Coroutine::getuid();
        self::$set[$cid] = 1;
    }

    public static function getStatus() :int
    {
        $cid = Coroutine::getuid();
        return self::$set[$cid] ?? 1;
    }
}
