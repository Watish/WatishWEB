<?php

namespace Watish\WatishWEB\Service;

use Watish\Components\Attribute\Inject;

class Test2Service
{
    #[Inject(TestService::class)]
    public TestService $testService;

    public function hello(): string
    {
        return "Hello";
    }
}
