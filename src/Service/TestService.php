<?php

namespace Watish\WatishWEB\Service;

use Watish\Components\Attribute\Async;
use Watish\Components\Attribute\Inject;
use Watish\Components\Utils\Logger;
use Watish\Components\Utils\Promise\Promise;

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

    public function promise_do_something()
    {
        return new Promise(function (){
            Logger::info("do something");

           return new Promise(function (){
               Logger::info("do something in promise of promise");
               return "do something in promise of promise * 2";
           });
        });
    }

    public function promise_then_do_something()
    {
        return new Promise(function (){
            Logger::info("then do something");
            return "then do something";
        });
    }

    public function promise_finally_do_something(): Promise
    {
        return new Promise(function (){
            Logger::info("finally do something");
            throw new \Exception("finally do something");
        });
    }
}
