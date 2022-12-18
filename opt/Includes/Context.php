<?php

namespace Watish\Components\Includes;

use Exception;
use Illuminate\Database\Connection;
use Predis\Client;
use Swoole\Coroutine;
use Watish\Components\Struct\Request;
use Watish\Components\Struct\Response;
use Watish\Components\Utils\ConnectionPool;
use Watish\Components\Utils\Logger;
use Watish\Components\Utils\ProcessSignal;
use Watish\Components\Utils\Worker\WorkerSignal;
use Watish\Components\Utils\WServ;

/**
 * @Description 上下文管理器
 */
class Context
{
    private array $set;
    private array $globalSet;
    private array $processes;
    private Connection $sqlConnection;
    private ConnectionPool $pdoPool;
    private ConnectionPool $redisPool;
    private int $workerId;
    private int $workerNum;
    /**
     * @var null
     */
    private $workerPool;
    private $lock;
    public function __construct()
    {
        $this->set = [];
        $this->processes = [];
        $this->globalSet = [];
        $this->workerId = 0;
        $this->workerNum = 0;
        $this->workerPool = null;
    }

    /**
     * @param mixed $lock
     */
    public function setLock(mixed $lock): void
    {
        $this->lock = $lock;
    }
    /**
     * @param null $workerPool
     */
    public function setWorkerPool($workerPool): void
    {
        $this->workerPool = $workerPool;
    }

    private function signalWorker(string $signal): void
    {
        $cid = $this->getCoUid();
        $worker_id = $this->workerId;
        Logger::debug("Signaling Workers From Cid #$cid","Worker#{$worker_id}");
        $this->lock->lock();
        for($i=0;$i<($this->workerNum);$i++)
        {
            if($i == $this->workerId)
            {
                continue;
            }
            $process = $this->workerPool->getProcess($i);
            $socket = $process->exportSocket();
            $socket->send($signal);
        }
        $this->lock->unlock();
    }

    public function GetGlobalSet():array
    {
        return $this->globalSet;
    }

    /**
     * @param int $workerNum
     */
    public function setWorkerNum(int $workerNum): void
    {
        $this->workerNum = $workerNum;
    }

    /**
     * @return int
     */
    public function getWorkerNum(): int
    {
        return $this->workerNum;
    }

    /**
     * @param int $workerId
     */
    public function setWorkerId(int $workerId): void
    {
        $this->workerId = $workerId;
    }

    /**
     * @return int
     */
    public function getWorkerId(): int
    {
        return $this->workerId;
    }

    /**
     * @param $serv
     * @return void
     */
    public function setServ($serv): void
    {
        $cid = $this->getCoUid();
        $this->set[$cid]["Serv"] = $serv;
    }

    /**
     * @throws Exception
     */
    public function getServ()
    {
        $cid = $this->getCoUid();
        if(!isset($this->set[$cid]["Serv"]))
        {
            throw new Exception("Serv Not Defined");
        }
        return $this->set[$cid]["Serv"];
    }

    public function setSqlConnection(Connection $connection): void
    {
        $this->sqlConnection = $connection;
    }

    public function setPdoPool(ConnectionPool $pdoPool): void
    {
        $this->pdoPool = $pdoPool;
    }

    public function abort():void
    {
        $cid = $this->getCoUid();
        $this->set[$cid]["abort"] = true;
        Logger::debug("Cid {$cid} Aborted!");
    }

    public function isAborted() :bool
    {
        $cid = $this->getCoUid();
        if(!isset($this->set[$cid]["abort"]))
        {
            return false;
        }
        return $this->set[$cid]["abort"];
    }

    public function global_Set_Response(string $key,$response):void
    {
        $this->globalSet[$key] = $response;
    }

    public function global_Set(string $key,string $value,bool $sig=true):void
    {
        $this->globalSet[$key] = $value;
        if($sig)
        {
            $signal = WorkerSignal::KV_Set($key,$value);
            $this->signalWorker($signal);
        }
    }

    public function global_Get($key)
    {
        return $this->globalSet[$key] ?? null;
    }

    public function global_Del($key,bool $sig=true) :void
    {
        unset($this->globalSet[$key]);
        if($sig)
        {
            $signal = WorkerSignal::KV_Del($key);
            $this->signalWorker($signal);
        }
    }

    public function globalSet_Add(string $key,mixed $item,string $uuid,bool $sig=true): void
    {
        if(!isset($this->globalSet[$key]))
        {
            $this->globalSet[$key] = [];
        }
        $this->globalSet[$key][$uuid] = $item;
        if($sig)
        {
            $signal = WorkerSignal::Set_Add($key,$uuid,$item);
            $this->signalWorker($signal);
        }
    }

    public function globalSet_Add_Response(string $key,$response,string $uuid):void
    {
        if(!isset($this->globalSet[$key]))
        {
            $this->globalSet[$key] = [];
        }
        $this->globalSet[$key][$uuid] = $response;
    }

    public function globalSet_Del($key,$uuid,bool $sig=true): void
    {
        unset($this->globalSet[$key][$uuid]);
        if($sig)
        {
            $signal = WorkerSignal::Set_Del($key,$uuid);
            $this->signalWorker($signal);
        }
    }

    public function globalSet_Exists($key,$uuid): bool
    {
        return isset($this->globalSet[$key][$uuid]);
    }

    public function globalSet_Get($key,$uuid):mixed
    {
        return $this->globalSet[$key][$uuid];
    }

    public function globalSet_keys($key):array
    {
        if(isset($this->globalSet[$key]))
        {
            return array_keys($this->globalSet[$key]);
        }
        return [];
    }

    public function globalSet_items($key):array
    {
        return array_values($this->globalSet[$key]);
    }

    public function global_Exists($key) :bool
    {
        return isset($this->globalSet[$key]);
    }

    public function globalSet_PushAll(string $key,string $msg): void
    {
        $signal = WorkerSignal::Set_Push_All($key,$msg);
        if($this->global_Exists($key))
        {
            $response_list = $this->globalSet_items($key);
            foreach ($response_list as $response)
            {
                $response->push($msg);
            }
        }
        $this->signalWorker($signal);
    }

    public function globalSet_Push(string $key,string $uuid,string $msg): void
    {
        $signal = WorkerSignal::Set_Push($key,$uuid,$msg);
        if($this->globalSet_Exists($key,$uuid))
        {
            $response = $this->globalSet_Get($key,$uuid);
            $response->push($msg);
        }
        $this->signalWorker($signal);
    }

    public function global_Push(string $key,string $msg):void
    {
        $signal = WorkerSignal::KV_Push($key,$msg);
        if($this->global_Exists($key))
        {
            $response = $this->global_Get($key);
            $response->push($msg);
        }
        $this->signalWorker($signal);
    }

    public function json(array|object $data,int $statusCode=200 ,string $reason = ""):void
    {
        $response = $this->getResponse();
        $response->header("content-type","application/json");
        $response->status($statusCode,$reason);
        $response->end(json_encode($data));
    }

    public function html(string $html , int $statusCode=200 , string $reason = ""):void
    {
        $response = $this->getResponse();
        $response->status($statusCode,$reason);
        $response->header("content-type","text/html; charset=utf-8");
        $response->end($html);
    }

    private function getPdo() :\PDO
    {
        return Database::getPdo();
    }

    /**
     * @param ConnectionPool $redisPool
     */
    public function setRedisPool(ConnectionPool $redisPool): void
    {
        $this->redisPool = $redisPool;
    }

    public function getRedis() :Client
    {
        $cid = $this->getCoUid();
        $client = Database::redis();
        $this->set[$cid]["Redis"] = $client;
        return $client;
    }

    /**
     * @param \Swoole\Http\Request $request
     * @return void
     */
    public function setRequest(\Swoole\Http\Request $request): void
    {
        $cid = $this->getCoUid();
        $this->set[$cid]["Request"] = $request;
    }

    /**
     * @return Request
     * @throws Exception
     */
    public function getRequest(): Request
    {
        $cid = $this->getCoUid();
        if(!isset($this->set[$cid]["Request"]))
        {
            throw new Exception("Request Undefined");
        }
        return new Request($this->set[$cid]["Request"]);
    }

    /**
     * @param \Swoole\Http\Response $response
     * @return void
     */
    public function setResponse(\Swoole\Http\Response $response):void
    {
        $cid = $this->getCoUid();
        $this->set[$cid]["Response"] = $response;
    }

    /**
     * @return Response|null
     */
    public function getResponse(): Response|null
    {
        $cid = $this->getCoUid();
        if(!isset($this->set[$cid]["Response"]))
        {
            return null;
        }
        return new Response($this->set[$cid]["Response"]);
    }

    /**
     * @return WServ
     * @throws Exception
     */
    public function Server(): WServ
    {
        return new WServ($this->getServ());
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function Set(string $key,mixed $value) :void
    {
        $cid = $this->getCoUid();
        $this->set[$cid][$key] = $value;
    }

    /**
     * @param array $process
     * @return void
     */
    public function setProcesses(array $process) :void
    {
        $this->processes = $process;
    }

    /**
     * @throws Exception
     */
    public function getProcess(string $key)
    {
        if(!$this->processExists($key))
        {
            throw new Exception("Process Undefined");
        }
        return $this->processes[$key];
    }

    public function processExists(string $key) :bool
    {
        return isset($this->processes[$key]);
    }

    /**
     * @param int $fd
     * @return bool
     * @throws Exception
     */
    public function ExistsFd(int $fd) :bool
    {
        return $this->getServ()->exists($fd);
    }

    /**
     * @throws Exception
     */
    public function Get(string $key) :mixed
    {
        $cid = $this->getCoUid();
        if(!isset($this->set[$cid][$key]))
        {
            throw new Exception("Key Not Found : $key");
        }
        return $this->set[$cid][$key];
    }

    /**
     * @param string $key
     * @return bool
     */
    public function Exists(string $key) :bool
    {
        $cid = $this->getCoUid();
        return isset($this->set[$cid][$key]);
    }

    /**
     * @throws Exception
     */
    public function AsyncTask(\Closure $closure) :void
    {
        if(!isset($this->set[$this->getCoUid()]["Serv"]))
        {
            throw new Exception("Serv Undefined");
        }

        $taskProcessList = $this->getProcess("Task");
        shuffle($taskProcessList);
        $taskProcess = $taskProcessList[0];
        $socket = $taskProcess->exportSocket();
        $socket->send(ProcessSignal::AsyncTask($closure));
    }

    private function getCoUid() : int
    {
        return Coroutine::getuid();
    }

    public function reset():void
    {
        $cid = $this->getCoUid();
        unset($this->set[$cid]);
        Database::reset();
        Logger::debug("Cid {$cid} Reset");
    }
}
