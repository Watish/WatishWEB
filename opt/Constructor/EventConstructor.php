<?php

namespace Watish\Components\Constructor;

use Watish\Components\Attribute\Event;
use Watish\Components\Utils\AttributeLoader\AttributeLoader;
use Watish\Components\Utils\Logger;

class EventConstructor
{

    public static function init() :void
    {
        $classLoader = ClassLoaderConstructor::getClassLoader("event");
        $classList = $classLoader->getClasses();
//        Logger::info($classList);
        $attributeLoader = new AttributeLoader($classList);
        $eventAttrs = $attributeLoader->getClassAttributes(Event::class);
//        Logger::info($eventAttrs);
        $eventSet = [];
        foreach ($eventAttrs as $className => $list_event_arr)
        {
            if($list_event_arr["count"] > 0)
            {
                $eventName = $list_event_arr["attributes"][0]["params"][0];
                if($eventName)
                {
                    $eventSet[$eventName] = $className;
                    Logger::info("{$eventName} -> Class:{$className}","Event");
                    continue;
                }
            }
            $eventSet[$className] = $className;
            Logger::info("{$className} -> Class:{$className}","Event");

        }
        \Watish\Components\Utils\Event\Event::setEventSet($eventSet);
    }
}