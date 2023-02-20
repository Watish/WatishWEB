<?php

namespace Watish\Components\Utils\Lock;

use League\CLImate\TerminalObject\Basic\Tab;
use Swoole\Coroutine;
use Watish\Components\Utils\Logger;
use Watish\Components\Utils\Table;

class GlobalLock
{
    private static \Swoole\Table $table;
    private static int $size;
    public static function lock(string $name='default',int $timeOut = 5) :bool
    {
        MultiLock::lock($name);
        $key = self::lock_key($name);
        $startTime = time();
        while(1)
        {
            if(time() - $startTime > $timeOut)
            {
                MultiLock::unlock($name);
                return false;
            }
            if(!self::$table->exists($key))
            {
                self::$table->set($key,["lock"=>1]);
                break;
            }elseif(!self::$table->get($key,"lock")){
                self::$table->set($key,["lock"=>1]);
                break;
            }
            Coroutine::sleep(0.001);
        }
        return true;
    }

    public static function unlock(string $name="default") :void
    {
        $key = self::lock_key($name);
        self::$table->set($key,["lock"=>0]);
        MultiLock::unlock($name);
    }

    public static function setTable(\Swoole\Table $table,int $size): void
    {
        self::$table = $table;
        self::$size = $size;
    }


    private static function lock_key($name):string
    {
        $key = substr(md5($name),8,16);
        $words = explode(',','a,b,c,d,e,f,g,h,i,j,k,l,m,n,o,p,q,r,s,t,u,v,w,x,y,z');
        $index = 0;
        foreach ($words as $word)
        {
            $index++;
            if($index>9)
            {
                $index = 0;
            }
            $key = str_replace($word,$index,$key);
        }
        $key = (int)$key;
        $size = (int)(self::$size * 0.75);
        $name = $key%$size + 1;
        return "LOCK_{$name}";
    }
}
