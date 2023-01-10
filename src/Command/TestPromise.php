<?php

namespace Watish\WatishWEB\Command;

use Watish\Components\Attribute\Command;
use Watish\Components\Attribute\Inject;
use Watish\Components\Utils\Logger;
use Watish\Components\Utils\Promise\Promise;
use Watish\WatishWEB\Service\TestService;

#[Command("promise","test")]
class TestPromise implements CommandInterface
{
    #[Inject(TestService::class)]
    private TestService $testService;

    public function handle(): void
    {
        $this->testService->promise_do_something()
            ->then(fn()=>$this->testService->promise_then_do_something())
            ->then(fn()=>$this->testService->promise_finally_do_something())
            ->catch(fn(\Exception $exception)=>Logger::exception($exception))
            ->then(fn()=>Logger::info("End"));
    }

}
