<?php



namespace Watish\WatishWEB\Controller;



use Watish\Components\Attribute\Inject;

use Watish\Components\Attribute\Middleware;

use Watish\Components\Attribute\Path;

use Watish\Components\Attribute\Prefix;

use Watish\Components\Struct\Request;

use Watish\WatishWEB\Middleware\TestMiddleware;

use Watish\WatishWEB\Model\User;

use Watish\WatishWEB\Service\BaseService;



#[Prefix('/hello')]

#[Middleware([TestMiddleware::class])]

class PROXY_8ae601672803576_HelloController

{

    #[Inject(BaseService::class)]

    public $baseService;



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

