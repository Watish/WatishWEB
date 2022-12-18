<?php

namespace Watish\WatishWEB\Service;

use Swoole\Coroutine;
use Watish\Components\Attribute\Aspect;
use Watish\Components\Attribute\Async;
use Watish\Components\Attribute\Inject;
use Watish\Components\Utils\Logger;
use Watish\WatishWEB\Aspect\TestAspect;

class TestService
{

    #[Inject(Test2Service::class)]
    private Test2Service $test2Service;

    #[Async]
    #[Aspect(TestAspect::class)]
    public function sayHello(string $hello="hello"): void
    {
        Coroutine::sleep(3);
        Logger::debug("Say Hello");
    }
}
