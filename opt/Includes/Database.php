<?php

namespace Watish\Components\Includes;

use Illuminate\Database\ConnectionInterface;
use PDO;
use Predis\Client;
use Swoole\Coroutine;
use Watish\Components\Constructor\RedisPoolConstructor;
use Watish\Components\Struct\DatabaseExtend;
use Watish\Components\Utils\ConnectionPool;
use Watish\Components\Utils\PDOPool;

class Database
{
    private static ConnectionPool $pdoPool;
    private static ConnectionPool $redisPool;
    private static ConnectionInterface $sqlConnection;

    private static $clientSet = [];
    private static bool $enablePool = false;

    public static function mysql() : DatabaseExtend
    {
        return new DatabaseExtend(self::$sqlConnection,null,null,self::$enablePool);
    }

    public static function enablePDOPool() :void
    {
        PDOPool::startPool();
        self::$enablePool = true;
    }

    public static function enableRedisPool() :void
    {
        RedisPoolConstructor::startPool();
    }

    public static function redis() :Client
    {
        return self::$redisPool->get();
    }

    public static function putRedis(Client $client): void
    {
        self::$redisPool->put($client);
    }

    /**
     * @param ConnectionPool $redisPool
     */
    public static function setRedisPool(ConnectionPool $redisPool): void
    {
        self::$redisPool = $redisPool;
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

    }
}
