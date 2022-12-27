<?php

namespace Watish\WatishWEB\Middleware;

use Watish\Components\Struct\Request;
use Watish\Components\Struct\Response;

class TestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Response $response)
    {
        $response->header("test","test");
    }

}
