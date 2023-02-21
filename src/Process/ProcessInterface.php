<?php

namespace Watish\WatishWEB\Process;

use Swoole\Process;
use Watish\Components\Utils\Process\Messenger;

interface ProcessInterface
{
    public function execute(Process $process, Messenger $messager): void;
}
