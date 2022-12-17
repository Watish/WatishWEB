<?php

namespace Watish\WatishWEB\Command;

use Watish\Components\Attribute\Command;

#[Command("show_keys","redis")]
class ShowRedisKeys implements CommandInterface
{
    public function handle(): void
    {
        // TODO: Implement handle() method.
    }

}
