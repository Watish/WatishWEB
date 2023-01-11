<?php

namespace Watish\Components\Utils;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Swoole\Coroutine;
use Watish\Components\Includes\Database;
use Watish\Components\Utils\ConnectionPool;
use Watish\Components\Utils\Logger;
use Watish\Components\Utils\Table;

class PDOPool
{
    private static ConnectionPool $pdoPool;

    public static function init(): void
    {
        $database_config = Table::get("database_config");
        $server_config = Table::get("server_config");
        if($database_config["mysql"]["enable"])
        {
            $host = $database_config["mysql"]["host"];
            $port = $database_config["mysql"]["port"];
            $user = $database_config["mysql"]["username"];
            $password = $database_config["mysql"]["password"];
            $database = $database_config["mysql"]["database"];
            $charset = $database_config["mysql"]["charset"];
            $collation = $database_config["mysql"]["collation"];
            $dsn = "mysql:host={$host};dbname={$database};charset={$charset};collation={$collation};port={$port}";

            $pdoPool = new ConnectionPool(function () use ($dsn,$user,$password){
                return new \PDO($dsn,$user,$password);
            },(int)($database_config["mysql"]["max_pool_count"]/$server_config["worker_num"])+2,(int)($database_config["mysql"]["min_pool_count"]/$server_config["worker_num"])+1,360,'pdo_pool');
            self::$pdoPool = $pdoPool;
        }
    }

    public static function startPool() :void
    {
        if(!DATABASE_CONFIG["mysql"]["enable"])
        {
            return;
        }
        Coroutine::create(function (){
            self::$pdoPool->startPool();
            Coroutine::sleep(2);
            Logger::debug("Pdo Pool Started","PdoPool");
            self::$pdoPool->watching();
        });
    }

    public static function getPdo() :mixed
    {
        if(!DATABASE_CONFIG["mysql"]["enable"])
        {
            return null;
        }
        return self::$pdoPool->get();
    }

    public static function putPdo($connection): void
    {
        if(!DATABASE_CONFIG["mysql"]["enable"])
        {
            return;
        }
        self::$pdoPool->put($connection);
    }

    /**
     * @return Connection
     */
    public static function getSqlConnection(): Connection
    {
        return self::$sqlConnection;
    }

    /**
     * @return ConnectionPool
     */
    public static function getPdoPool(): ConnectionPool
    {
        return self::$pdoPool;
    }

    /**
     * @return Capsule
     */
    public static function getCapsule(): Capsule
    {
        return self::$capsule;
    }
}
