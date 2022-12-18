<?php

namespace Watish\Components\Constructor;

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
        $taskProcessList = self::$taskProcessList;
        shuffle($taskProcessList);
        $taskProcess = $taskProcessList[0];
        $socket = $taskProcess->exportSocket();
        $socket->send(ProcessSignal::AsyncTask($closure));
    }
}
