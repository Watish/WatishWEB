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
        Coroutine::create(function () {
           Coroutine::sleep(2);
           Logger::warn("Test","TestProcess");
        });
    }

}
