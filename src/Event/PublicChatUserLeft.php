<?php

namespace Watish\WatishWEB\Event;

use Watish\Components\Attribute\Event;
use Watish\Components\Includes\Context;

#[Event("public_chat_user_left")]
class PublicChatUserLeft implements EventInterface
{
    public function trigger(array $data): void
    {
        $push_key = "public_chat_channel";
        $user_pool_key = "public_chat_channel_users";
        $user_pool_set = Context::global_Get($user_pool_key);

        if (!$user_pool_set or count($user_pool_set) <= 1) {
            Context::globalSet_PushAll($push_key, json_encode([
                "type" => "system",
                "msg" => "当前房间仅剩1人，即将关闭"
            ]));
        }
    }
}
