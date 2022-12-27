<?php

namespace Watish\WatishWEB\Controller;

use Watish\Components\Attribute\Inject;
use Watish\Components\Attribute\Middleware;
use Watish\Components\Attribute\Path;
use Watish\Components\Attribute\Prefix;
use Watish\Components\Struct\Request;
use Watish\WatishWEB\Middleware\TestMiddleware;
use Watish\WatishWEB\Service\BaseService;

#[Prefix('/hello')]
#[Middleware([TestMiddleware::class])]
class HelloController
{
    #[Inject(BaseService::class)]
    public BaseService $baseService;

    #[Path('/index')]
    public function index(Request $request) :array
    {
        return [
            "msg" => $this->baseService->toArray(["Hello",'World'])
        ];
    }

    #[Path('/user/{name}',['GET','POST'])]
    public function msg(Request $request) :array
    {
        return [
            "msg" => "hello ".$request->route('name')
        ];
    }
}
