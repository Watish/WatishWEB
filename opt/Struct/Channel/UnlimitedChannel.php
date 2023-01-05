<?php

namespace Watish\Components\Struct\Channel;

use Swoole\Coroutine;

class UnlimitedChannel
{
    private static array $channelHash = [];
    private static array $waitHash = [];

    public static function push(string $name,mixed $data) :void
    {
        self::init($name);
        $need_resume = false;
        if(empty(self::$channelHash[$name]))
        {
            $yield_cid = array_pop(self::$waitHash[$name]);
            $need_resume = (bool)$yield_cid;
        }
        self::$channelHash[$name][] = $data;
        if($need_resume)
        {
            Coroutine::resume($yield_cid);
        }
    }

    public static function pop(string $name)
    {
        self::init($name);
        $cid = Coroutine::getCid();
        if(empty(self::$channelHash[$name]))
        {
            self::$waitHash[$name][] = $cid;
            Coroutine::yield();
        }
        return array_pop(self::$channelHash[$name]);
    }

    public function isEmpty(string $name) :bool
    {
        return empty(self::$channelHash[$name]);
    }

    public function length(string $name) :int
    {
        return isset(self::$channelHash[$name]) ? count(self::$channelHash[$name]) : 0;
    }

    public function close(string $name): void
    {
        self::$channelHash[$name] = [];
        if(isset(self::$waitHash[$name]))
        {
            foreach (self::$waitHash[$name] as $cid)
            {
                Coroutine::resume($cid);
            }
        }
    }

    private static function init(string $name) :void
    {
        if(!isset(self::$channelHash[$name]))
        {
            self::$channelHash[$name] = [];
        }
        if(!isset(self::$waitHash[$name]))
        {
            self::$waitHash[$name] = [];
        }
    }
}
