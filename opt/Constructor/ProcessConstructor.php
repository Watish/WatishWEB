<?php

namespace Watish\Components\Constructor;

use Exception;
use Watish\Components\Attribute\Crontab;
use Watish\Components\Includes\Process;
use Watish\Components\Utils\AttributeLoader\AttributeLoader;
use Watish\Components\Utils\Injector\ClassInjector;
use Watish\Components\Utils\Logger;

class ProcessConstructor
{
    private static array $processNameSet;
    private static array $processList;
    private static array $pidProcessSet;
    private static Process $process;

    /**
     * @throws Exception
     */
    public static function init(): void
    {
        $process = new Process();
        self::$process = $process;

        $processNameSet = [];
        $processList = [];
        $pidProcessSet = [];

        if(SERVER_CONFIG["register_process_auto"])
        {
            self::scanProcess();
        }else{
            require_once BASE_DIR . '/config/process.php';
            do_register_process($process);
        }

        $executed_list = $process->GetAllProcess();
        //Start Process
        foreach ($executed_list as $process_array)
        {
            $process_name = $process_array["name"];
            $list_executed_array = $process_array["callback"];
            $worker_num = $process_array["worker"];
            for($i=1;$i<=$worker_num;$i++)
            {
                $process = new \Swoole\Process(function (\Swoole\Process $proc) use ($list_executed_array){
                    try{
                        call_user_func_array($list_executed_array,[$proc]);
                    }catch (Exception $e)
                    {
                        Logger::exception($e);
                    }
                },false,SOCK_DGRAM, true);
                $status = $process->start();
                $pid = $process->pid;
                if($status)
                {
                    $pidProcessSet[$pid] = $process;
                    $processNameSet[$process_name][] = $process;
                    $processList[] = $process;
                }else{
                    Logger::error("Process: {$process_name}#{$i} ,PID:$pid, Error");
                }
            }
        }
        self::$processNameSet = $processNameSet;
        self::$processList = $processList;
        self::$pidProcessSet = $pidProcessSet;
    }

    /**
     * @return array
     */
    public static function getPidProcessSet(): array
    {
        return self::$pidProcessSet;
    }

    /**
     * @return array
     */
    public static function getProcessList(): array
    {
        return self::$processList;
    }

    /**
     * @return array
     */
    public static function getProcessNameSet(): array
    {
        return self::$processNameSet;
    }

    /**
     * @throws Exception
     */
    private static function scanProcess(): void
    {
        $classLoader = ClassLoaderConstructor::getClassLoader("process");
        $attributeLoader = new AttributeLoader($classLoader->getClasses());
        $attributes = $attributeLoader->getClassAttributes(\Watish\Components\Attribute\Process::class);
        $i = 0;
        foreach ($attributes as $class => $item) {
            $i ++;
            if ($item["count"] > 0) {
                $process_name = $item["attributes"][0]["params"][0] ?? "process_{$i}";
                $process_num = $item["attributes"][0]["params"][1] ?? 1;
                $callback = [ClassInjector::getInjectedInstance($class),"execute"];
                self::$process->Register($callback,$process_name,$process_num);
            }
        }
    }
}
