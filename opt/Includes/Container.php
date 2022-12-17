<?php

namespace Watish\Components\Includes;

use Illuminate\Database\ConnectionInterface;
use Predis\Client;
use Watish\Components\Struct\Database;
use Watish\Components\Utils\ConnectionPool;

class Container
{
    private static ConnectionPool $pdoPool;
    private static array $data;
    private static ConnectionPool $redisPool;
    private static ConnectionInterface $sqlConnection;

    public static function mysql() :Database
    {
        return new Database(self::$sqlConnection,null,null,self::getPdo());
    }

    public static function putPdo(\PDO $pdo) :void
    {
        self::$pdoPool->put($pdo);
    }

    public static function redis() :Client
    {
        return self::$redisPool->get();
    }

    public static function putRedis(Client $client): void
    {
        self::$redisPool->put($client);
    }

    public static function getPdo() :\PDO
    {
        return self::$pdoPool->get();
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
}
