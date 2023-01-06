<?php

namespace Watish\Components\Constructor;

use Swoole\Coroutine;
use Watish\Components\Utils\Lock\MultiLock;
use Watish\Components\Utils\ProcessSignal;

class AsyncTaskConstructor
{
    private static array $taskProcessList;

    public static function init(array $taskProcessList): void
    {
        self::$taskProcessList = $taskProcessList;
    }

    public static function make(\Closure $closure) :void
    {
        Coroutine::create(function () use ($closure){
            $taskProcessList = self::$taskProcessList;
            shuffle($taskProcessList);
            $taskProcess = $taskProcessList[0];
            MultiLock::lock("async_task");
            $socket = $taskProcess->exportSocket();
            $socket->send(ProcessSignal::AsyncTask($closure));
            MultiLock::unlock("async_task");
        });
    }
}
