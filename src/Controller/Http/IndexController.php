<?php

namespace Watish\WatishWEB\Controller\Http;

use Swoole\Coroutine;
use Watish\Components\Attribute\Inject;
use Watish\Components\Attribute\Path;
use Watish\Components\Constructor\ViewConstructor;
use Watish\Components\Includes\Context;
use Watish\Components\Struct\Request;
use Watish\WatishWEB\Service\BaseService;

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
            "msg" => "hello ".$name
        ];
    }
}
