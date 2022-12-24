<?php

namespace Watish\WatishWEB\Controller\Http;

use Watish\Components\Attribute\Inject;
use Watish\Components\Attribute\Middleware;
use Watish\Components\Attribute\Path;
use Watish\Components\Attribute\Prefix;
use Watish\Components\Includes\Context;
use Watish\Components\Struct\Request;
use Watish\Components\Utils\Table;
use Watish\WatishWEB\Middleware\CorsMiddleware;
use Watish\WatishWEB\Service\BaseService;
use Watish\WatishWEB\Service\TestService;

class IndexController
{
    #[Inject(BaseService::class)]
    public BaseService $baseService;

    public function index(Request $request): array
    {
        return [
            "Method" => $request->getMethod(),
            "Params" => $request->all()
        ];
    }

    #[Path("/hello/{name}")]
    public function hello_somebody(Request $request):array
    {
        return [
            "msg" => "hello ".$request->route("name")
        ];
    }
}
