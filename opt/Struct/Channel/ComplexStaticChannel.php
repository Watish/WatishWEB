<?php

namespace Watish\Components\Struct\Channel;

use Swoole\Coroutine;

class ComplexStaticChannel
{
    private static array $task_list = [];
    private static array $result_hash = [];
    private static array $wait_list = [];
    private static array $client_wait_set = [];


    public static function pushTask(string $name,mixed $data) :void
    {
        self::init($name);
        $need_resume = false;
        if(empty(self::$task_list[$name]))
        {
            $yield_cid = array_pop(self::$wait_list[$name]);
            $need_resume = (bool)$yield_cid;
        }
        array_unshift(self::$task_list[$name],[
            "cid" => Coroutine::getCid(),
            "data" => $data
        ]);
//        self::$task_list[$name][] = [
//            "cid" => Coroutine::getCid(),
//            "data" => $data
//        ];
        if($need_resume)
        {
            Coroutine::resume($yield_cid);
        }
    }

    public static function popTask(string $name) :array|null
    {
        self::init($name);
        $cid = Coroutine::getCid();
        if(empty(self::$task_list[$name])) {
            self::$wait_list[$name][] = $cid;
            Coroutine::yield();
        }
        return array_pop(self::$task_list[$name]);
    }

    public static function waitResult(string $name) :mixed
    {
        self::init($name);
        $cid = Coroutine::getCid();
        if(isset(self::$result_hash[$name][$cid]))
        {
            return self::$result_hash[$name][$cid];
        }
        self::$client_wait_set[$name][$cid] = time();
        Coroutine::yield();
        unset(self::$client_wait_set[$name][$cid]);
        unset(self::$result_hash[$name][$cid]);
        return self::$result_hash[$name][$cid];
    }

    public static function pushResult(string $name,int $cid,mixed $data): void
    {
        self::init($name);
        self::$result_hash[$name][$cid] = $data;
        if(isset(self::$client_wait_set[$name][$cid]))
        {
            Coroutine::resume($cid);
        }
    }

    private static function init(string $name) :void
    {
        if(!isset(self::$result_hash[$name]))
        {
            self::$result_hash[$name] = [];
        }
        if(!isset(self::$task_list[$name]))
        {
            self::$task_list[$name] = [];
        }
        if(!isset(self::$client_wait_set[$name]))
        {
            self::$client_wait_set[$name] = [];
        }
        if(!isset(self::$wait_list[$name]))
        {
            self::$wait_list[$name] = [];
        }
    }
}
