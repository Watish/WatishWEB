<?php

namespace Watish\WatishWEB\Process;

use Swoole\Coroutine;
use Watish\Components\Attribute\Process;
use Watish\Components\Utils\Logger;

#[Process("Test")]
class TestProcess implements ProcessInterface
{
    public function execute(\Swoole\Process $process): void
    {
        Logger::info("Hello World");
        Coroutine::create(function (){
            while (1)
            {
                Coroutine::sleep(1);
            }
        });
    }

}
