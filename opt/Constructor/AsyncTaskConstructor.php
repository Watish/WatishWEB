<?php

namespace Watish\Components\Constructor;

use Exception;
use Swoole\Coroutine;
use Swoole\Process;
use Watish\Components\Kernel\Process\TaskProcess;
use Watish\Components\Utils\ENV;
use Watish\Components\Utils\Injector\ClassInjector;
use Watish\Components\Utils\Lock\MultiLock;
use Watish\Components\Utils\Logger;
use Watish\Components\Utils\Pid\PidHelper;
use Watish\Components\Utils\Process\ProcessManager;
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
            $messager = ProcessManager::make("AsyncTask");
            $process = new \Swoole\Process(function (\Swoole\Process $proc) use($i,$messager) {
                Process::signal(SIGTERM,function () use ($proc,$i){
                    $proc_num = $i-1;
                    Logger::info("Task Process#{$proc_num} is going to shutdown...");
                    $proc->exit(0);
                });
                try{
                    ClassInjector::getInjectedInstance(TaskProcess::class)->execute($proc,$messager);
                }catch (Exception $e)
                {
                    Logger::error($e->getMessage(),"Process");
                }
            },false,SOCK_DGRAM, true);
            $process->start();
            PidHelper::add("AsyncTask",$process->pid);
            self::$taskProcessList[] = $process;
        }
        self::$init = true;
    }

    public static function make(\Closure $closure) :void
    {
        Coroutine::create(function () use ($closure){
            Coroutine::sleep(0.001);
//            $taskProcessList = self::$taskProcessList;
//            shuffle($taskProcessList);
//            $taskProcess = $taskProcessList[0];
//            MultiLock::lock("async_task");
            $messenger = ProcessManager::get_messenger_by_name("AsyncTask");
            if(!is_null($messenger))
            {
                $messenger->write(ProcessSignal::AsyncTask($closure));
            }
//            $socket = $taskProcess->exportSocket();
//            $socket->send(ProcessSignal::AsyncTask($closure));
//            MultiLock::unlock("async_task");
        });
    }
}
