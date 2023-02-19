<?php

namespace Watish\Components\Utils\Event;

use Swoole\Coroutine;
use Watish\Components\Utils\Injector\ClassInjector;
use Watish\Components\Utils\Logger;

class Event
{
    private static array $eventSet = [];

    public static function emit(string $name,array $data,bool $async=false) :void
    {
        if(!isset(self::$eventSet[$name]))
        {
            Logger::error("Event {$name} 不存在","Event");
            return;
        }
        $className = self::$eventSet[$name];
        if($async)
        {
            Coroutine::create(function () use ($className,$data){
               Coroutine::sleep(0.001);
               self::trigger($className,$data);
            });
        }else{
            self::trigger($className,$data);
        }
    }

    /**
     * @param array $eventSet
     */
    public static function setEventSet(array $eventSet): void
    {
        self::$eventSet = $eventSet;
    }

    private static function trigger(string $className,array $data) :void
    {
        $obj = ClassInjector::getInjectedInstance($className);
        try {
            $obj->trigger($data);
        }catch (\Exception $exception)
        {
            Logger::exception($exception);
        }
    }

}