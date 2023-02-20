<?php

namespace Watish\Components\Constructor;

use Cron\CronExpression;
use Opis\Closure\SerializableClosure;
use Swoole\Coroutine;
use Swoole\Process;
use Watish\Components\Attribute\Crontab;
use Watish\Components\Kernel\Process\CrontabProcess;
use Watish\Components\Kernel\Process\TaskProcess;
use Watish\Components\Utils\AttributeLoader\AttributeLoader;
use Watish\Components\Utils\ENV;
use Watish\Components\Utils\Injector\ClassInjector;
use Watish\Components\Utils\Lock\MultiLock;
use Watish\Components\Utils\Logger;
use Watish\Components\Utils\Pid\PidHelper;
use Watish\Components\Utils\Process\ProcessManager;

class CrontabConstructor
{
    private static bool $init = false;
    /**
     * @var Process[]
     */
    private static array $taskProcessList = [];

    public static function init(): void
    {
        if(self::$init)
        {
            return;
        }

        $crontab_process_num = (int)ENV::getConfig("Process")["CRONTAB_PROCESS_NUM"];
        for($i=1;$i<=$crontab_process_num;$i++)
        {
            $messager = ProcessManager::make("Crontab");
            $process = new Process(function (Process $proc) use ($i,$messager){
                Process::signal(SIGTERM,function() use($proc,$i) {
                    $proc_num = $i-1;
                    Logger::info("Crontab Process#{$proc_num} is going to shutdown...","Crontab");
                    $proc->exit(0);
                });
                try{
                    ClassInjector::getInjectedInstance(CrontabProcess::class)->execute($proc,$messager);
                }catch (Exception $e)
                {
                    Logger::error($e->getMessage(),"Process");
                }
            },false,SOCK_DGRAM, true);
            $process->start();
            PidHelper::add("Crontab",$process->pid);
            self::$taskProcessList[] = $process;
        }
        self::scanTask();
        self::$init = true;
    }

    public static function addCron(string $rule,string $name,callable $callback) :void
    {
        Coroutine::create(function () use ($rule,$name,$callback){
            if(self::$init)
            {
                Coroutine::sleep(0.001);
            }
            $msg = json_encode([
                "type" => "crontab",
                "rule" => $rule,
                "name" => $name,
                "callback" => serialize($callback)
            ]);
            self::sendData($msg);
        });
    }

    public static function delCron(string $name) :void
    {
        Coroutine::create(function () use ($name){
            if(self::$init)
            {
                Coroutine::sleep(0.001);
            }
            $msg = json_encode([
                "type" => "delete",
                "name" => $name
            ]);
            self::sendData($msg);
        });
    }

    public static function addTime(int $time,string $name,\Closure $closure): void
    {
        Coroutine::create(function () use ($time,$name,$closure){
            $msg = json_encode([
                "type" => "time",
                "time" => $time,
                "name" => $name,
                "closure" => @serialize(new SerializableClosure($closure))
            ]);
            self::sendData($msg);
        });
    }

    private static function scanTask() :void
    {
        $classLoader = ClassLoaderConstructor::getClassLoader("crontab");
        $attributeLoader = new AttributeLoader($classLoader->getClasses());
        $attributes = $attributeLoader->getClassAttributes(Crontab::class);
        $i = 0;
        foreach ($attributes as $class => $item) {
            $i ++;
            if ($item["count"] > 0) {
                $cron_rule = $item["attributes"][0]["params"][0];
                Logger::debug($cron_rule, "Crontab");
                self::addCron($cron_rule,"cron_{$i}",[ClassInjector::getInjectedInstance($class),"execute"]);
            }
        }
    }

    private static function sendData(string $msg): void
    {
        Coroutine::create(function () use ($msg) {
//            $taskProcessList = self::$taskProcessList;
//            shuffle($taskProcessList);
//            $process = $taskProcessList[0];
//            MultiLock::lock("CrontabProcess");
            $messager = ProcessManager::get_messager_by_name("Crontab");
            $messager->write($msg);
//            $socket = $process->exportSocket();
//            $socket->send($msg);
//            MultiLock::unlock("CrontabProcess");
        });
    }

}
