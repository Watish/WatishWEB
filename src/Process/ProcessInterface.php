<?php

namespace Watish\WatishWEB\Process;

interface ProcessInterface
{
    public function execute(\Swoole\Process $process): void;
}
