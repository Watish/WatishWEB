<?php

namespace Watish\WatishWEB\Service;

use Watish\Components\Attribute\Inject;

class TestService
{

    #[Inject(Test2Service::class)]
    private Test2Service $test2Service;

    public function sayHello(): string
    {
        return "Hello";
    }
}
