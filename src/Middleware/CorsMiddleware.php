<?php

namespace Watish\WatishWEB\Middleware;

use Watish\Components\Attribute\GlobalMiddleware;
use Watish\Components\Struct\Request;
use Watish\Components\Struct\Response;

#[GlobalMiddleware]
class CorsMiddleware implements MiddlewareInterface
{
    public function handle(Request $request,Response $response): void
    {
        $response->header("Access-Control-Allow-Origin", "*");
        $response->header("Access-Control-Allow-Credentials", true);
    }
}
