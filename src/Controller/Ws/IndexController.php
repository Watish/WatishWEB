<?php

namespace Watish\WatishWEB\Controller\Ws;

use Swoole\Coroutine;
use Watish\Components\Attribute\Path;
use Watish\Components\Attribute\Prefix;
use Watish\Components\Struct\Request;
use Watish\Components\Struct\Response;

#[Prefix('/ws')]
class IndexController
{
    #[Path('/')]
    public function handle(Request $request,Response $response): void
    {
        $response->upgrade();
        Coroutine::create(function () use ($response){
            while (1)
            {
                $frame = $response->recv();
                if($frame->isClosed())
                {
                    return;
                }
                $response->push($frame->data);
            }
        });
    }
}