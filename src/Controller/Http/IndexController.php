<?php

namespace Watish\WatishWEB\Controller\Http;

use Swoole\Coroutine;
use Watish\Components\Attribute\Inject;
use Watish\Components\Attribute\Path;
use Watish\Components\Constructor\AsyncTaskConstructor;
use Watish\Components\Constructor\CrontabConstructor;
use Watish\Components\Constructor\LocalFilesystemConstructor;
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
}
