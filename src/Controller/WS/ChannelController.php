<?php

namespace Watish\WatishWEB\Controller\WS;

use Swoole\Coroutine;
use Watish\Components\Attribute\Path;
use Watish\Components\Attribute\Prefix;
use Watish\Components\Includes\Context;
use Watish\Components\Utils\Logger;
use Watish\MyWebsocket\Event\PublicChatUserLeft;

#[Prefix("/channel")]
class ChannelController
{
    #[Path("/public")]
    public function public_channel(Context $context): void
    {
        $request = $context->getRequest();
        $response = $context->getResponse();

        $params = $request->GetAll();
        $validator = new \Cake\Validation\Validator();
        $validator->requirePresence("name")
            ->notEmptyString("name")
            ->lengthBetween("name", [6,12]);
        //Validate UserName
        $errors = $validator->validate($params);

        if ($errors) {
            $context->json([
                "Ok" => false,
                "Msg" => "name invalid"
            ]);
            $context->abort();
            return;
        }
        $userName = (string)$params["name"];

        $Key = "public_chat_channel";

        //UserName Duplicated
        if ($context->globalSet_Exists($Key, $userName)) {
            $context->json([
                "Ok" => false,
                "Msg" => "User Already Exists"
            ]);
            $context->abort();
            return;
        }

        $user_pool_key = "public_chat_channel_users";

        //Upgrade to ws
        Logger::info("User $userName Joined");
        $response->upgrade();
        Coroutine::create(function () use ($response, &$context, $Key, $user_pool_key, $userName) {
            Logger::info($context->globalSet_keys($Key));
            $context->globalSet_Add_Response($Key, $response, $userName);
            $context->globalSet_Add($user_pool_key, null, $userName);
            $context->globalSet_PushAll($Key, json_encode([
                "type" => "user_joined",
                "name" => $userName
            ]));
            while (1) {
                $frame = $response->recv();
                if ($frame->isClosed()) {
                    $context->globalSet_Del($Key, $userName);
                    $response->close();
                    //User Left
                    Coroutine::create(function () use (&$context, $Key, $user_pool_key, $userName) {
                        $context->globalSet_PushAll($Key, json_encode([
                            "type" => "user_left",
                            "name" => $userName
                        ]));
                        $context->globalSet_Del($user_pool_key, $userName);
                    });
                    (new PublicChatUserLeft())->trigger($context);
                    Logger::info("User $userName Left");
                    break;
                }
                $msg = $frame->data;
                if ($msg == "[HeartBeat]") {
                    continue;
                }
                if ($msg == "[UserPool]") {
                    $response->push(json_encode($context->globalSet_keys($user_pool_key)));
                }
                $context->globalSet_PushAll($Key, json_encode([
                    "type" => "msg",
                    "user" => $userName,
                    "msg" => $msg
                ]));
            }
        });
    }
}
