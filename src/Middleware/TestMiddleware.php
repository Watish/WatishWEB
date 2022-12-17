<?php

namespace Watish\WatishWEB\Middleware;

use Watish\Components\Includes\Context;
use Watish\Components\Utils\Logger;

class TestMiddleware implements MiddlewareInterface
{
    /**
     * @throws \Exception
     */
    public function handle(Context $context)
    {
        $request = $context->getRequest();
        $response = $context->getResponse();
        // TODO: Implement handle() method.
        Logger::info($request->GetAll());
    }
}
