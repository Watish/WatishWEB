<?php

namespace Watish\WatishWEB\Command;

use Swoole\Coroutine;
use Watish\Components\Attribute\Command;
use Watish\Components\Struct\Channel\UnlimitedChannel;
use Watish\Components\Utils\Lock\MultiLock;
use Watish\Components\Utils\Logger;

#[Command("hello","command")]
class HelloCommand implements CommandInterface
{
    public function handle(): void
    {
        Coroutine::create(function () {
            for($i=1;$i<=99;$i++)
            {
                $data = UnlimitedChannel::pop("hello");
                Logger::info($data);
            }
        });
        for($i=1;$i<=99;$i++)
        {
            Coroutine::create(function () use ($i){
                UnlimitedChannel::push("hello",$i);
            });
        }


    }

}
