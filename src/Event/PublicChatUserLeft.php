<?php

namespace Watish\WatishWEB\Event;

use Watish\Components\Includes\Context;

class PublicChatUserLeft implements EventInterface
{
    public function trigger(Context $context): void
    {
        // TODO: Implement trigger() method.
        $push_key = "public_chat_channel";
        $user_pool_key = "public_chat_channel_users";
        $user_pool_set = $context->global_Get($user_pool_key);

        if (!$user_pool_set or count($user_pool_set) <= 1) {
            $context->globalSet_PushAll($push_key, json_encode([
                "type" => "system",
                "msg" => "当前房间仅剩1人，即将关闭"
            ]));
        }
    }
}
