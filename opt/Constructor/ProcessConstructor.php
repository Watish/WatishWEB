<?php

namespace Watish\Components\Constructor;

use Exception;
use Watish\Components\Includes\Process;
use Watish\Components\Utils\Logger;

class ProcessConstructor
{
    private static array $processNameSet;
    private static array $processList;
    private static array $pidProcessSet;
    private static Process|\Swoole\Process $process;

    /**
     * @throws Exception
     */
    public static function init(): void
    {
        $process = new Process();
        $processNameSet = [];
        $processList = [];
        $pidProcessSet = [];
        require_once BASE_DIR . '/config/process.php';
        do_register_process($process);
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
                        Logger::error($e->getMessage(),"Process");
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
        self::$process = $process;
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
}
