<?php

namespace Watish\WatishWEB\Command;

use Swoole\Coroutine;
use Watish\Components\Attribute\Command;
use Watish\Components\Utils\Lock\MultiLock;
use Watish\Components\Utils\Logger;

#[Command("hello","command")]
class HelloCommand implements CommandInterface
{
    public function handle(): void
    {
        for($i=1;$i<=99;$i++)
        {
            Coroutine::create(function () use ($i){
                MultiLock::lock();
                Logger::info("Hello {$i}");
                MultiLock::unlock();
            });
        }
    }

}
