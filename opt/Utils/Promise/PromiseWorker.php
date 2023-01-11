<?php

namespace Watish\Components\Utils\Promise;

use \Closure;
use Opis\Closure\SerializableClosure;
use Swoole\Coroutine;
use Watish\Components\Struct\Channel\UnlimitedChannel;
use Watish\Components\Utils\Logger;

class PromiseWorker
{
    private static array $workerHash = [];

    /**
     * @var UnlimitedChannel[]
     */
    private static array $waitHash = [];

    public static function init(string $name) :void
    {
        if(!isset(self::$workerHash[$name]))
        {
            if(!isset(self::$waitHash[$name]))
            {
                self::$waitHash[$name] = new UnlimitedChannel();
            }
            self::startWorker($name);
        }
    }

    public static function pushResolve(string $name,Closure $closure) :void
    {
        self::init($name);
        Logger::debug("Push Resolve","Promise");
        self::$waitHash[$name]->push([
            "type" => "resolve",
            "closure" => $closure
        ]);
    }

    public static function pushReject(string $name,Closure $closure) :void
    {
        self::init($name);
        Logger::debug("Push Reject","Promise");
        self::$waitHash[$name]->push([
            "type" => "reject",
            "closure" => $closure
        ]);
    }

    private static function startWorker(string $name) :void
    {
        Coroutine::create(function ()use($name){
            self::$workerHash[$name] = Coroutine::getCid();
            $startTime = time();
            $res = null;
            $error = false;
            $catchException = null;
            $count = 0;
            //Handle Queue
            while(1)
            {
                $startTime = time();
                //Wait Channel
                while(1)
                {
                    if($count > 0 and self::$waitHash[$name]->isEmpty() and (time() - $startTime)>5)
                    {
                        return;
                    }
                    if(!self::$waitHash[$name]->isEmpty())
                    {
                        break;
                    }
                    Coroutine::sleep(CPU_SLEEP_TIME);
                }

                //Handle Closure
                $arr = self::$waitHash[$name]->pop();
                $count++;
                $startTime = time();
                $type = $arr["type"];
                $closure = $arr["closure"];

                //Catch
                if($type == "reject" and $error)
                {
                    try{
                        $res = $closure($catchException);
                        $error = false;
                    }catch (\Exception $exception)
                    {
                        $catchException = $exception;
                        $error = true;
                    }
                }

                if($error)
                {
                    continue;
                }

                //Then
                if($type == "resolve" and !$error)
                {
                    try{
                        if($count<=1)
                        {
                            //First
                            $res = $closure(); // Promise Closure Result
                        }else{
                            //Then
                            if($res instanceof Promise)
                            {
                                //Is Promise
                                $promise_closure = $res->getClosure(); //Second Promise Closure
                                $res = $promise_closure(); //Second Promise Closure Result
                                $res = $closure($res); //Second Promise Then Result
                            }else{
                                //Not Promise
                                $res = $closure($res);
                            }
                        }
                    }catch (\Exception $exception)
                    {
                        $catchException = $exception;
                        $error = true;
                    }
                }

                Coroutine::sleep(CPU_SLEEP_TIME);
            }
        });
    }

}
