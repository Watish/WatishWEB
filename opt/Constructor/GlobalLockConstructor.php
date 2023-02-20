<?php

namespace Watish\Components\Constructor;

use Swoole\Table;
use Watish\Components\Utils\Agent\ResponseAgent;
use Watish\Components\Utils\Lock\GlobalLock;

class GlobalLockConstructor
{
    private static Table $table;

    public static function init() :void
    {
        $size = 1024*CPU_NUM;
        if($size > 4096)
        {
            $size = 4096;
        }
        $table = new Table($size,0.2);
        $table->column("lock",Table::TYPE_INT);
        $table->create();
        self::$table = $table;
        GlobalLock::setTable($table,$size);
    }

    /**
     * @return Table
     */
    public static function getTable(): Table
    {
        return self::$table;
    }



}