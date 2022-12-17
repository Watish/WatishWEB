<?php

namespace Watish\WatishWEB\Controller\WS;

use Watish\Components\Includes\Context;
use Watish\Components\Utils\Logger;
use Watish\WatishWEB\Attribute\Prefix;

#[Prefix("/home")]
class HomeController
{
    #[Prefix("/")]
    public function index(Context $context): void
    {
        $response = $context->getResponse();
        $request = $context->getRequest();
        $params = $request->GetAll();

        $response->upgrade();
        $context->globalSet_Add("home_response", $response, $params["token"]);
        $fd = $response->fd;
        while (1) {
            $frame = $response->recv();
            if ($frame->isClosed()) {
                Logger::debug("Closed");
                break;
            }
        }
    }
}
