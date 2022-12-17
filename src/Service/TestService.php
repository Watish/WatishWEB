<?php

namespace Watish\WatishWEB\Service;

use Watish\Components\Attribute\Aspect;
use Watish\Components\Attribute\Inject;
use Watish\WatishWEB\Aspect\TestAspect;

class TestService
{

    #[Inject(Test2Service::class)]
    private Test2Service $test2Service;

    #[Aspect(TestAspect::class)]
    public function sayHello(string $hello="hello"): string
    {
        return $hello;
    }
}
