<?php

namespace Watish\Components\Constructor;

use Exception;
use Swoole\Coroutine;
use Watish\Components\Kernel\Process\TaskProcess;
use Watish\Components\Utils\ENV;
use Watish\Components\Utils\Lock\MultiLock;
use Watish\Components\Utils\Logger;
use Watish\Components\Utils\ProcessSignal;

class AsyncTaskConstructor
{
    private static array $taskProcessList = [];
    private static bool $init = false;

    public static function init(): void
    {
        if(self::$init)
        {
            return;
        }
        $task_process_num = (int)ENV::getConfig("Process")["TASK_PROCESS_NUM"];
        for($i=1;$i<=$task_process_num;$i++)
        {
            $process = new \Swoole\Process(function (\Swoole\Process $proc){
                try{
                    (new TaskProcess())->execute($proc);
                }catch (Exception $e)
                {
                    Logger::error($e->getMessage(),"Process");
                }
            },false,SOCK_DGRAM, true);
            $process->start();
            self::$taskProcessList[] = $process;
        }
        self::$init = true;
    }

    public static function make(\Closure $closure) :void
    {
        Coroutine::create(function () use ($closure){
            $taskProcessList = self::$taskProcessList;
            $taskProcess = $taskProcessList[rand(0,count($taskProcessList)-1)];
            MultiLock::lock("async_task");
            $socket = $taskProcess->exportSocket();
            $socket->send(ProcessSignal::AsyncTask($closure));
            MultiLock::unlock("async_task");
        });
    }
}
