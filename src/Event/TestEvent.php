<?php

namespace Watish\WatishWEB\Event;

use Watish\Components\Attribute\Event;
use Watish\Components\Utils\Logger;

#[Event("test")]
class TestEvent implements EventInterface
{
    public function trigger(array $data): void
    {
        if(!isset($data["msg"]))
        {
            return;
        }
        Logger::info($data["msg"],"TestEvent");
    }
}