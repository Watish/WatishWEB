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

#[Prefix("")]
class IndexController
{
    #[Inject(BaseService::class)]
    public BaseService $baseService;

    #[Path("/")]
    public function index(Request $request): array
    {
        return [
            "Method" => $request->getMethod(),
            "Params" => $request->all()
        ];
    }

    #[Path("/hello")]
    public function hello(Request $request):array
    {
        return [
            "msg" => "Hello"
        ];
    }
}
