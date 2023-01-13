<?php

namespace Watish\Components\Kernel\Process;

use Cron\CronExpression;
use Swoole\Coroutine;
use Watish\Components\Utils\Logger;
use Watish\WatishWEB\Process\ProcessInterface;

class CrontabProcess implements ProcessInterface
{
    private array $cronHash = [];

    public function execute(\Swoole\Process $process): void
    {
        Coroutine::create(function () use ($process){
            Coroutine::enableScheduler();

            //Receiver
            Coroutine::create(function () use ($process){
                $socket = $process->exportSocket();
                while(1)
                {
                    $msg = $socket->recv();
                    if(!$msg)
                    {
                        continue;
                    }
                    $msg = json_decode($msg,true);
                    $type = $msg["type"];
                    $name = $msg["name"];
                    if($type == "crontab")
                    {
                        $rule = $msg["rule"];
                        $this->cronHash[$name] = [
                            "cron" => new CronExpression($rule),
                            "type" => $type,
                            "callback" => unserialize($msg["callback"])
                        ];
                    }
                    if($type == "time")
                    {
                        $time = $msg["time"];

                        $this->cronHash[$name] = [
                            "time" => $time,
                            "type" => $type,
                            "closure" => @unserialize($msg["closure"])
                        ];
                    }
                    if($type == "delete")
                    {
                        unset($this->cronHash[$name]);
                    }
                }
            });

            //Handler
            Coroutine::create(function () use ($process){
                while(1)
                {
                    Coroutine::sleep(1);
                    if(count($this->cronHash)<=0)
                    {
                        continue;
                    }
                    foreach ($this->cronHash as $name => $cronArray)
                    {
                        Coroutine::create(function ()use($cronArray,$name)
                        {
                            try{
                                if($cronArray["type"] == "crontab")
                                {
                                    $cron = $cronArray["cron"];
                                    $next_date = $cron->getNextRunDate('now',0,true,SERVER_CONFIG["timezone"])->format("Y-m-d H:i:s");
                                    $callback = $cronArray["callback"];
                                    if ($next_date == date("Y-m-d H:i:s")) {
                                        call_user_func_array($callback, []);
                                    }
                                }
                                if($cronArray["type"] == "time")
                                {
                                    $time = $cronArray["time"];
                                    if(time() >= $time)
                                    {
                                        $closure = $cronArray["closure"];
                                        @$closure();
                                        unset($this->cronHash[$name]);
                                    }
                                }
                            }catch (\Exception $exception)
                            {
                                Logger::exception($exception);
                            }

                        });
                    }
                }
            });


        });
    }
}
