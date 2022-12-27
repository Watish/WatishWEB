<?php

namespace Watish\WatishWEB\Service;

use Watish\Components\Attribute\Async;
use Watish\Components\Attribute\Inject;
use Watish\Components\Utils\Logger;

class TestService
{
    #[Inject(BaseService::class)]
    public BaseService $baseService;

    #[Async]
    public function asyncHello(): void
    {
        Logger::info("Hello");
    }

    public function hello(string $name) :string
    {
        return "hello {$name}";
    }
}
