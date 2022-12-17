<?php

namespace Watish\Components\Utils;
use Swoole\Database\PDOConfig;

class PDOPool
{

    private \Swoole\Database\PDOPool $pool;

    public function __construct(array $mysql_config)
    {
        $pdoConfig = new PDOConfig();
        $pdoConfig->withHost($mysql_config["host"])
            ->withPort($mysql_config["port"])
            // ->withUnixSocket('/tmp/mysql.sock')
            ->withDbName($mysql_config["database"])
            ->withCharset($mysql_config["charset"])
            ->withUsername($mysql_config["username"])
            ->withPassword($mysql_config["password"]);
        $pool = new \Swoole\Database\PDOPool($pdoConfig,$mysql_config["pool_count"]);
        $this->pool = $pool;
    }

    public function getPool(): \Swoole\Database\PDOPool
    {
        return $this->pool;
    }
}
