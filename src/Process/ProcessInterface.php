<?php

namespace Watish\WatishWEB\Process;

use Swoole\Process;
use Watish\Components\Utils\Process\Messager;

interface ProcessInterface
{
    public function execute(Process $process,Messager $messager): void;
}
