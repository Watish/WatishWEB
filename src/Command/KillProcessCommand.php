<?php

namespace Watish\WatishWEB\Command;

use Swoole\Process;
use Watish\Components\Attribute\Command;
use Watish\Components\Constructor\LocalFilesystemConstructor;
use Watish\Components\Utils\Logger;
use Watish\Components\Utils\Pid\PidHelper;

#[Command('kill','process')]
class KillProcessCommand implements CommandInterface
{
    public function handle(): void
    {
        $nameList = PidHelper::getNameList();
        foreach ($nameList as $name)
        {
            Logger::info("Killing name [{$name}] ...");
            PidHelper::killName($name);
        }
    }
}