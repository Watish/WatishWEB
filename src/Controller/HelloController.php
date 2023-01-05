<?php

namespace Watish\WatishWEB\Controller;

use Swoole\Coroutine;
use Watish\Components\Attribute\Inject;
use Watish\Components\Attribute\Middleware;
use Watish\Components\Attribute\Path;
use Watish\Components\Attribute\Prefix;
use Watish\Components\Constructor\AsyncTaskConstructor;
use Watish\Components\Includes\Context;
use Watish\Components\Struct\Request;
use Watish\Components\Utils\Lock\GlobalLock;
use Watish\Components\Utils\Logger;
use Watish\WatishWEB\Middleware\TestMiddleware;
use Watish\WatishWEB\Model\User;
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
        $cid = Coroutine::getCid();
        AsyncTaskConstructor::make(function () use ($cid){
            Logger::info($cid);
        });
        return [
            "msg" => $this->baseService->toArray(["Hello",'World'])
        ];
    }

    #[Path('/user/{name}',['GET','POST'])]
    public function msg(Request $request) :array
    {
        GlobalLock::lock("user_name");
        Logger::info("Lock");
        $cid = Coroutine::getCid();
        $worker_Id = Context::getWorkerId();
        Logger::info("worker_id $worker_Id cid $cid");
        Logger::info("UnLock");
        GlobalLock::unlock('user_name');
        return [
            "msg" => "hello ".$request->route('name')
        ];
    }

    #[Path('/user/info/{user_id}')]
    public function user_info(Request $request):array
    {
        $user_id = $request->route("user_id");
        $res = User::where("user_id",$user_id)->first();
        return [
            "Ok" => (bool)$res,
            "Data" => $res ? $res : null
        ];
    }
}
