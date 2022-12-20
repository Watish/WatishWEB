<?php

namespace Watish\WatishWEB\Middleware;

use Watish\Components\Includes\Context;
use Watish\Components\Struct\Request;
use Watish\Components\Struct\Response;

interface MiddlewareInterface
{
    public function handle(Request $request,Response $response);
}
