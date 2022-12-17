<?php

namespace Watish\WatishWEB\Command;

use Watish\Components\Attribute\Command;
use Watish\Components\Utils\Logger;

#[Command("hello","command")]
class HelloCommand implements CommandInterface
{
    public function handle(): void
    {
        Logger::info("Hello");
    }

}
