<?php

namespace Watish\Components\Utils\Lock;

use League\CLImate\TerminalObject\Basic\Tab;
use Swoole\Coroutine;
use Watish\Components\Utils\Table;

class GlobalLock
{
    private static array $init = [];
    public static function lock(string $name='default') :void
    {
        self::init($name);
        $cid = Coroutine::getCid();
        MultiLock::lock($name);
        while(1)
        {
            if(!Table::get(self::lock_key($name)))
            {
                break;
            }
            Coroutine::sleep(CPU_SLEEP_TIME);
        }
        Table::set(self::lock_key($name),true);
    }

    public static function unlock(string $name="default") :void
    {
        self::init($name);
        Table::set(self::lock_key($name),false);
        MultiLock::unlock($name);
    }

    private static function init(string $name="default") :void
    {
        if(!isset(self::$init[$name])) {
            $lock_key = self::lock_key($name);
            $lock_wait_list_key = self::lock_wait_list_key($name);
            if (!Table::exists($lock_key)) {
                Table::set($lock_key, false);
            }
            if (!Table::exists($lock_wait_list_key)) {
                Table::set($lock_wait_list_key, []);
            }
            self::$init[$name] = true;
        }
    }

    private static function lock_key($name="default"):string
    {
        return "LOCK_{$name}";
    }

    private static function lock_wait_list_key($name="default"):string
    {
        return "LOCK_WAIT_LIST_{$name}";
    }
}
