<?php

namespace Watish\Components\Constructor;

use Swoole\Coroutine;
use Watish\Components\Includes\Database;
use Watish\Components\Utils\ConnectionPool;
use Watish\Components\Utils\Logger;
use Watish\Components\Utils\Table;

class RedisPoolConstructor
{
    private static ConnectionPool|null $redisPool;

    public static function init() :ConnectionPool|null
    {
        $database_config = Table::get("database_config");
        $server_config = Table::get("server_config");
        if($database_config["redis"]["enable"])
        {
            $redisPool = new ConnectionPool(function () use ($database_config){
                $redis = new \Predis\Client($database_config["redis"]["parameters"],$database_config["redis"]["options"]);
                $redis->connect();
                return $redis;
            },(int)($database_config["redis"]["pool_max_count"]/$server_config["worker_num"])+2,(int)($database_config["redis"]["pool_max_count"]/$server_config["worker_num"])+1);
            Database::setRedisPool($redisPool);
        }else{
            $redisPool = null;
        }
        self::$redisPool = $redisPool;
        return $redisPool;
    }

    public static function startPool() :void
    {
        Coroutine::create(function (){
           self::$redisPool->startPool();
           Coroutine::sleep(2);
           Logger::debug("RedisPool Started","RedisPool");
//           self::$redisPool->watching();
        });
    }

    /**
     * @return ConnectionPool|null
     */
    public static function getRedisPool(): ConnectionPool|null
    {
        return self::$redisPool;
    }

}
