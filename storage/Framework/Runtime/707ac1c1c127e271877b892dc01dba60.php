<?php



namespace Watish\WatishWEB\Middleware;



use Watish\Components\Struct\Request;

use Watish\Components\Struct\Response;



class PROXY_8c1471672803576_TestMiddleware implements MiddlewareInterface

{

    public function handle(Request $request, Response $response)

    {

        $response->header("test","test");

    }



}

