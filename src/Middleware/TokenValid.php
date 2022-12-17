<?php

namespace Watish\WatishWEB\Middleware;

use Watish\Components\Includes\Context;
use Watish\WatishWEB\Service\AuthService;

class TokenValid implements MiddlewareInterface
{
    public function handle(Context $context)
    {
        $request = $context->getRequest();
        $params = $request->GetAll();
        if (!isset($params["token"])) {
            $context->json([
                "Ok" => false,
                "Msg" => "token invalid"
            ]);
            $context->abort();
            return;
        }
        $token = $params["token"];
        if (!is_string($token)) {
            $context->json([
                "Ok" => false,
                "Msg" => "token invalid"
            ]);
            $context->abort();
            return;
        }
        $authService = new AuthService();
        if (!$authService->check_token_valid($token, $context->Database())) {
            $context->json([
                "Ok" => false,
                "Msg" => "token invalid"
            ]);
            $context->abort();
            return;
        }
    }
}
