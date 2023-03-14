<?php

namespace Watish\WatishWEB\Process;

use Swoole\Coroutine;
use Watish\Components\Attribute\Process;
use Watish\Components\Utils\Logger;
use Watish\Components\Utils\Process\Messenger;

#[Process("Test")]
class TestProcess implements ProcessInterface
{
    public function execute(\Swoole\Process $process, Messenger $messager): void
    {
        Logger::info("Hello World");
        Coroutine::create(function (){
            while (1)
            {
                // Logger::info("111");
                Coroutine::sleep(1);
            }
        });
    }

}
