<?php



namespace Watish\WatishWEB\Middleware;



use Watish\Components\Attribute\GlobalMiddleware;

use Watish\Components\Includes\Context;



#[GlobalMiddleware]

class PROXY_8a3291671462743_CorsMiddleware implements MiddlewareInterface

{

    public function handle(Context $context): void

    {

        $response = $context->getResponse();

        $response->header("Access-Control-Allow-Origin", "*");

        $response->header("Access-Control-Allow-Credentials", true);

        $context->setResponse($response->response);

    }

}

