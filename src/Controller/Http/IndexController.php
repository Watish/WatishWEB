<?php

namespace Watish\WatishWEB\Controller\Http;

use Swoole\Coroutine;
use Watish\Components\Attribute\Inject;
use Watish\Components\Attribute\Path;
use Watish\Components\Constructor\ViewConstructor;
use Watish\Components\Includes\Context;
use Watish\Components\Struct\Request;
use Watish\Components\Struct\SharedMemory\TempList;
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
        return [
            "msg" => "hello ".$name
        ];
    }
}
