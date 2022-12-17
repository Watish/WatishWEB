<?php

namespace Watish\WatishWEB\Task;

use Watish\Components\Attribute\Crontab;

#[Crontab("* * * * *")]
class HelloTask implements TaskInterface
{
    public function execute(): void
    {
//        Logger::debug("Hello","HelloTask");
    }
}
