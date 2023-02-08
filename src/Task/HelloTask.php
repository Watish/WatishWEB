<?php

namespace Watish\WatishWEB\Task;

use Watish\Components\Attribute\Crontab;
use Watish\Components\Utils\Logger;

#[Crontab("* * * * *")]
class HelloTask implements TaskInterface
{
    public function execute(): void
    {
//        Logger::info("Hello","HelloTask");
    }
}
