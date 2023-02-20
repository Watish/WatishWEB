<?php

namespace Watish\WatishWEB\Controller\Http;

use Swoole\Coroutine;
use Watish\Components\Attribute\Inject;
use Watish\Components\Attribute\Path;
use Watish\Components\Constructor\AsyncTaskConstructor;
use Watish\Components\Constructor\CrontabConstructor;
use Watish\Components\Constructor\ViewConstructor;
use Watish\Components\Includes\Context;
use Watish\Components\Struct\Request;
use Watish\Components\Struct\SharedMemory\TempList;
use Watish\Components\Utils\Logger;
use Watish\WatishWEB\Service\BaseService;
use Watish\WatishWEB\Service\TestService;

class IndexController
{
    #[Inject(BaseService::class)]
    public BaseService $baseService;

    #[Inject(TestService::class)]
    private TestService $testService;

    #[Path('/')]
    public function index(Request $request): string
    {
        AsyncTaskConstructor::make(function (){
            Logger::info("Async Task");
            CrontabConstructor::addTime(time()+20,"sayYes",function (){
                Logger::info("say yes in crontab from async task");
            });
        });
        CrontabConstructor::addTime(time()+10,"SayHello",function (){
            Logger::info("Crontab Say Hello");
            AsyncTaskConstructor::make(function (){
                Logger::info("Async Task in Crontab!");
            });
        });
        return ViewConstructor::render('index',[
            "title" => "Watish Web"
        ]);
    }

    #[Path('/test/promise')]
    public function test_promise(Request $request): array
    {
        $promise = $this->testService->promise_do_something()
            ->then(fn($res)=>Logger::info($res))
            ->then(fn($res)=>$this->testService->promise_then_do_something())
            ->then(fn($res)=>$this->testService->promise_finally_do_something())
            ->then(fn($res)=>Logger::info("Cannot Reach Here"))
            ->catch(fn($exception)=>Logger::exception($exception))
            ->then(fn()=>Logger::info("End"));
        return [
            "msg" => "ok"
        ];
    }

    #[Path("/hello/{name}")]
    public function hello_somebody(Request $request):array
    {
        $name = $request->route("name");
        return [
            "msg" => "hello ".$name
        ];
    }
}
