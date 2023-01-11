<?php

namespace Watish\Components\Includes;

use Exception;
use Swoole\Coroutine;
use Watish\Components\Struct\Request;
use Watish\Components\Struct\Response;
use Watish\Components\Utils\Lock\GlobalLock;
use Watish\Components\Utils\Lock\MultiLock;
use Watish\Components\Utils\Logger;
use Watish\Components\Utils\ProcessSignal;
use Watish\Components\Utils\Worker\WorkerSignal;
use Watish\Components\Utils\WServ;

/**
 * @Description 上下文管理器
 */
class Context
{
    private static array $set = [];
    private static array $globalSet = [];
    private static array $processes = [];
    private static int $workerId = -1;
    private static int $workerNum = 0;
    /**
     * @var null
     */
    private static $workerPool;

    /**
     * @param null $workerPool
     */
    public static function setWorkerPool($workerPool): void
    {
        self::$workerPool = $workerPool;
    }

    private static function signalWorker(string $signal): void
    {
        Coroutine::create(function () use ($signal){
            $cid = self::getCoUid();
            $worker_id = self::$workerId;
            Logger::debug("Signaling Workers From Cid #$cid","Worker#{$worker_id}");
            GlobalLock::lock('signalWorker');
            for($i=0;$i<(self::$workerNum);$i++)
            {
                if($i == self::$workerId)
                {
                    continue;
                }
                $process = self::$workerPool->getProcess($i);
                $socket = $process->exportSocket();
                $socket->send($signal);
            }
            GlobalLock::unlock("signalWorker");
        });
    }

    public static function GetGlobalSet():array
    {
        return self::$globalSet;
    }

    /**
     * @param int $workerNum
     */
    public static function setWorkerNum(int $workerNum): void
    {
        self::$workerNum = $workerNum;
    }

    /**
     * @return int
     */
    public static function getWorkerNum(): int
    {
        return self::$workerNum;
    }

    /**
     * @param int $workerId
     */
    public static function setWorkerId(int $workerId): void
    {
        self::$workerId = $workerId;
    }

    /**
     * @return int
     */
    public static function getWorkerId(): int
    {
        return self::$workerId;
    }

    /**
     * @param $serv
     * @return void
     */
    public static function setServ($serv): void
    {
        $cid = self::getCoUid();
        self::$set[$cid]["Serv"] = $serv;
    }

    /**
     * @throws Exception
     */
    public static function getServ()
    {
        $cid = self::getCoUid();
        if(!isset(self::$set[$cid]["Serv"]))
        {
            throw new Exception("Serv Not Defined");
        }
        return self::$set[$cid]["Serv"];
    }

    public static function abort():void
    {
        $cid = self::getCoUid();
        self::$set[$cid]["abort"] = true;
        Logger::debug("Cid {$cid} Aborted!");
    }

    public static function isAborted() :bool
    {
        $cid = self::getCoUid();
        if(!isset(self::$set[$cid]["abort"]))
        {
            return false;
        }
        return self::$set[$cid]["abort"];
    }

    public static function global_Set_Response(string $key,$response):void
    {
        self::$globalSet[$key] = $response;
    }

    public static function global_Set(string $key,string $value,bool $sig=true):void
    {
        self::$globalSet[$key] = $value;
        if($sig)
        {
            $signal = WorkerSignal::KV_Set($key,$value);
            self::signalWorker($signal);
        }
    }

    public static function global_Get($key)
    {
        return self::$globalSet[$key] ?? null;
    }

    public static function global_Del($key,bool $sig=true) :void
    {
        unset(self::$globalSet[$key]);
        if($sig)
        {
            $signal = WorkerSignal::KV_Del($key);
            self::signalWorker($signal);
        }
    }

    public static function globalSet_Add(string $key,mixed $item,string $uuid,bool $sig=true): void
    {
        if(!isset(self::$globalSet[$key]))
        {
            self::$globalSet[$key] = [];
        }
        self::$globalSet[$key][$uuid] = $item;
        if($sig)
        {
            $signal = WorkerSignal::Set_Add($key,$uuid,$item);
            self::signalWorker($signal);
        }
    }

    public static function globalSet_Add_Response(string $key,$response,string $uuid):void
    {
        if(!isset(self::$globalSet[$key]))
        {
            self::$globalSet[$key] = [];
        }
        self::$globalSet[$key][$uuid] = $response;
    }

    public static function globalSet_Del($key,$uuid,bool $sig=true): void
    {
        unset(self::$globalSet[$key][$uuid]);
        if($sig)
        {
            $signal = WorkerSignal::Set_Del($key,$uuid);
            self::signalWorker($signal);
        }
    }

    public static function globalSet_Exists($key,$uuid): bool
    {
        return isset(self::$globalSet[$key][$uuid]);
    }

    public static function globalSet_Get($key,$uuid):mixed
    {
        return self::$globalSet[$key][$uuid];
    }

    public static function globalSet_keys($key):array
    {
        if(isset(self::$globalSet[$key]))
        {
            return array_keys(self::$globalSet[$key]);
        }
        return [];
    }

    public static function globalSet_items($key):array
    {
        return array_values(self::$globalSet[$key]);
    }

    public static function global_Exists($key) :bool
    {
        return isset(self::$globalSet[$key]);
    }

    public static function globalSet_PushAll(string $key,string $msg): void
    {
        $signal = WorkerSignal::Set_Push_All($key,$msg);
        if(self::global_Exists($key))
        {
            $response_list = self::globalSet_items($key);
            foreach ($response_list as $response)
            {
                $response->push($msg);
            }
        }
        self::signalWorker($signal);
    }

    public static function globalSet_Push(string $key,string $uuid,string $msg): void
    {
        $signal = WorkerSignal::Set_Push($key,$uuid,$msg);
        if(self::globalSet_Exists($key,$uuid))
        {
            $response = self::globalSet_Get($key,$uuid);
            $response->push($msg);
        }
        self::signalWorker($signal);
    }

    public static function global_Push(string $key,string $msg):void
    {
        $signal = WorkerSignal::KV_Push($key,$msg);
        if(self::global_Exists($key))
        {
            $response = self::global_Get($key);
            $response->push($msg);
        }
        self::signalWorker($signal);
    }

    public static function json(array|object $data,int $statusCode=200 ,string $reason = ""):void
    {
        $response = self::getResponse();
        $response->header("content-type","application/json");
        $response->status($statusCode,$reason);
        $response->end(json_encode($data));
    }

    public static function html(string $html , int $statusCode=200 , string $reason = ""):void
    {
        $response = self::getResponse();
        $response->status($statusCode,$reason);
        $response->header("content-type","text/html; charset=utf-8");
        $response->end($html);
    }

    /**
     * @param Request $request
     * @return void
     */
    public static function setRequest(Request $request): void
    {
        $cid = self::getCoUid();
        self::$set[$cid]["Request"] = $request;
    }

    public static function getRequest(): Request|null
    {
        $cid = self::getCoUid();
        if(!isset(self::$set[$cid]["Request"]))
        {
            return null;
        }
        return self::$set[$cid]["Request"];
    }

    /**
     * @param Response $response
     * @return void
     */
    public static function setResponse(Response $response):void
    {
        $cid = self::getCoUid();
        self::$set[$cid]["Response"] = $response;
    }

    public static function getResponse(): Response|null
    {
        $cid = self::getCoUid();
        if(!isset(self::$set[$cid]["Response"]))
        {
            return null;
        }
        return self::$set[$cid]["Response"];
    }

    /**
     * @return WServ
     */
    public static function Server(): WServ
    {
        return new WServ(self::getServ());
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function Set(string $key,mixed $value) :void
    {
        $cid = self::getCoUid();
        self::$set[$cid][$key] = $value;
    }

    /**
     * @param array $process
     * @return void
     */
    public static function setProcesses(array $process) :void
    {
        self::$processes = $process;
    }

    /**
     * @throws Exception
     */
    public static function getProcess(string $key)
    {
        if(!self::processExists($key))
        {
            throw new Exception("Process Undefined");
        }
        return self::$processes[$key];
    }

    public static function processExists(string $key) :bool
    {
        return isset(self::$processes[$key]);
    }

    /**
     * @param int $fd
     * @return bool
     */
    public static function ExistsFd(int $fd) :bool
    {
        return self::getServ()->exists($fd);
    }

    public static function Get(string $key) :mixed
    {
        $cid = self::getCoUid();
        if(!isset(self::$set[$cid][$key]))
        {
            return null;
        }
        return self::$set[$cid][$key];
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function Exists(string $key) :bool
    {
        $cid = self::getCoUid();
        return isset(self::$set[$cid][$key]);
    }

    /**
     * @throws Exception
     */
    public static function AsyncTask(\Closure $closure) :void
    {
        if(!isset(self::$set[self::getCoUid()]["Serv"]))
        {
            throw new Exception("Serv Undefined");
        }

        $taskProcessList = self::getProcess("Task");
        shuffle($taskProcessList);
        $taskProcess = $taskProcessList[0];
        $socket = $taskProcess->exportSocket();
        $socket->send(ProcessSignal::AsyncTask($closure));
    }

    private static function getCoUid() : int
    {
        return Coroutine::getuid();
    }

    public static function reset():void
    {
        $cid = self::getCoUid();
        unset(self::$set[$cid]);
        Logger::debug("Cid {$cid} Reset");
    }
}
