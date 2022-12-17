<?php

use Watish\WatishWEB\Process\CrontabProcess;
use Watish\WatishWEB\Process\TaskProcess;

/**
 * @throws Exception
 */
function do_register_process(\Watish\Components\Includes\Process $process): void
{
    //Task Process
    $process->Register([TaskProcess::class,'execute'],'Task',swoole_cpu_num());

    //Crontab Process (Must Single!!!)
    $process->Register([CrontabProcess::class,'execute'],'Crontab',1);
}
