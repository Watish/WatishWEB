<?php

namespace Watish\WatishWEB\Task;

use Watish\Components\Attribute\Crontab;

#[Crontab("2 * * * *")]
class TestTask implements TaskInterface
{
    public function execute(): void
    {
        return;
    }
}
