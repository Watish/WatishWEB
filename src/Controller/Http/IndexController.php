<?php

namespace Watish\WatishWEB\Controller\Http;

use Swoole\Coroutine;
use Watish\Components\Attribute\Inject;
use Watish\Components\Attribute\Middleware;
use Watish\Components\Attribute\Path;
use Watish\Components\Attribute\Prefix;
use Watish\Components\Constructor\ViewConstructor;
use Watish\Components\Includes\Context;
use Watish\Components\Struct\Request;
use Watish\Components\Utils\Logger;
use Watish\Components\Utils\Table;
use Watish\WatishWEB\Middleware\CorsMiddleware;
use Watish\WatishWEB\Service\BaseService;
use Watish\WatishWEB\Service\TestService;

class IndexController
{
    #[Inject(BaseService::class)]
    public BaseService $baseService;

    #[Path('/')]
    public function index(Request $request): string
    {
        return ViewConstructor::render('index',[
            "title" => "Watish Web"
        ]);
    }

    #[Path("/hello/{name}")]
    public function hello_somebody(Request $request):array
    {
        $name = $request->route("name");
        Coroutine::create(function ()use($name){
            $worker_id = Context::getWorkerId();
            Context::global_Set("Hello",$worker_id.Coroutine::getCid().$name);
        });
        return [
            "msg" => "hello ".$name,
            "data" => Context::global_Get("Hello")
        ];
    }
}
