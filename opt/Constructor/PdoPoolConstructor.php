<?php

namespace Watish\Components\Constructor;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Connection;
use Swoole\Coroutine;
use Watish\Components\Includes\Database;
use Watish\Components\Utils\ConnectionPool;
use Watish\Components\Utils\Logger;
use Watish\Components\Utils\PDOPool;
use Watish\Components\Utils\Table;

class PdoPoolConstructor
{
    private static Capsule $capsule;
    private static Connection $sqlConnection;

    public static function init(): void
    {
        $database_config = Table::get("database_config");
        if($database_config["mysql"]["enable"])
        {
            PDOPool::init();
            $capsule =  new Capsule;
            $capsule->addConnection($database_config["mysql"]);
            $capsule->setAsGlobal();
            $capsule->bootEloquent();
            $sqlConnection = $capsule->getConnection("default");
            self::$capsule = $capsule;
            self::$capsule->setAsGlobal();
            self::$capsule->bootEloquent();
            self::$sqlConnection = $sqlConnection;
            Database::setSqlConnection($sqlConnection);
        }
    }

    /**
     * @return Connection
     */
    public static function getSqlConnection(): Connection
    {
        return self::$sqlConnection;
    }

    /**
     * @return Capsule
     */
    public static function getCapsule(): Capsule
    {
        return self::$capsule;
    }
}
