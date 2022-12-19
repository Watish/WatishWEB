<?php

namespace Watish\Components\Includes;

use Illuminate\Database\ConnectionInterface;
use Predis\Client;
use Swoole\Coroutine;
use Watish\Components\Struct\DatabaseExtend;
use Watish\Components\Utils\ConnectionPool;

class Database
{
    private static ConnectionPool $pdoPool;
    private static ConnectionPool $redisPool;
    private static ConnectionInterface $sqlConnection;

    private static $clientSet = [];

    public static function mysql() : DatabaseExtend
    {
        return new DatabaseExtend(self::$sqlConnection,null,null,self::getPdo());
    }

    public static function putPdo(\PDO $pdo) :void
    {
        self::$pdoPool->put($pdo);
    }

    public static function redis() :Client
    {
        $cid = Coroutine::getuid();
        $client = self::$redisPool->get();
        self::$clientSet["redis"][$cid] = $client;
        return $client;
    }

    public static function putRedis(Client $client): void
    {
        self::$redisPool->put($client);
    }

    public static function getPdo() :\PDO
    {
        $cid = Coroutine::getuid();
        $client = self::$pdoPool->get();
        self::$clientSet["pdo"][$cid] = $client;
        return $client;
    }

    /**
     * @param ConnectionPool $redisPool
     */
    public static function setRedisPool(ConnectionPool $redisPool): void
    {
        self::$redisPool = $redisPool;
    }

    /**
     * @param ConnectionPool $pdoPool
     */
    public static function setPdoPool(ConnectionPool $pdoPool): void
    {
        self::$pdoPool = $pdoPool;
    }

    /**
     * @param ConnectionInterface $sqlConnection
     */
    public static function setSqlConnection(ConnectionInterface $sqlConnection): void
    {
        self::$sqlConnection = $sqlConnection;
    }

    public static function reset(): void
    {
        $cid = Coroutine::getuid();
        if(isset(self::$clientSet["pdo"][$cid]))
        {
            $client = self::$clientSet["pdo"][$cid];
            self::$pdoPool->put($client);
            unset(self::$clientSet["pdo"][$cid]);
        }
        if(isset(self::$clientSet["redis"][$cid]))
        {
            $client = self::$clientSet["redis"][$cid];
            self::$redisPool->put($client);
            unset(self::$clientSet["redis"][$cid]);
        }
    }
}
