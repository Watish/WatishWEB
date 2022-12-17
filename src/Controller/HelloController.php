<?php

namespace Watish\WatishWEB\Controller;

use Swoole\Coroutine;
use Watish\Components\Attribute\Inject;
use Watish\Components\Attribute\Path;
use Watish\Components\Attribute\Prefix;
use Watish\Components\Includes\Context;
use Watish\Components\Utils\Logger;
use Watish\MyWebsocket\Middleware\TokenValid;
use Watish\WatishWEB\Service\AuthService;
use Watish\WatishWEB\Service\TestService;

#[Prefix("/test")]
class HelloController
{

    #[Inject(TestService::class)]
    public TestService $testService;

    #[Inject(AuthService::class)]
    public AuthService $authService;

    #[Path("")]
    public function index(Context $context): void
    {
        $request = $context->getRequest();
        $response = $context->getResponse();
        $database = $context->Database();
        //Upgrade To WS
        $response->upgrade();
        $context->AsyncTask(function () {
            $this->index_process("Hello , Async Task");
        });
        $response->push("Hello From Main".time());

        Coroutine::create(function () use ($response, $context) {
            $response->push("Hello From Coroutine1");
            $Key = "public_chat_channel";
            while (1) {
                $response->push("Hello From Coroutine1 ".time());
                $context->globalSet_PushAll($Key, json_encode([
                    "type" => "system",
                    "msg" => "Hello World"
                ]));
                Coroutine::sleep(5);
            }
        });

        while (1) {
            $response->push("Coroutine2 Receive Frame");
            $frame = $response->recv();
            if ($frame->isClosed()) {
                Logger::info("Closed!");
                $response->close();
                $context->abort();
                break;
            }
            Logger::info("Received!");
            if ($frame->data == "pool") {
                $response->push(json_encode([
                    "Ok" => true,
                ]));
            }
            $response->push(json_encode([
                "Ok" => true,
                "Msg" => $frame->data
            ]));
        }
        return;
    }

    #[Path("/async_task")]
    public function index_process(string $msg): void
    {
        Logger::info("Async Task index_process: $msg");
    }

    #[Path("/test_db")]
    public function test_db(Context $context): void
    {
        Logger::debug("Test DB 1");
        $database = $context->Database();
        $builder = $database->from("user");
        $count = $builder->count();
        $res = $builder->get();

        $context->json([
            "Ok" => true,
            "Res" => $res,
            "Count" => $count

        ]);
    }

    #[Path("/test_db2")]
    public function test_db_2(Context $context): void
    {
        Logger::debug("Test DB 2");
        $database = $context->Database();
        $builder = $database->from("user")->where("user_id", 1);
//        $res = $builder->get();
//        $count = $builder->count();
//        $first = $builder->first();
        $exists = $builder->exists();
        if (!$exists) {
            $context->json([
                "Ok" => false
            ]);
            $context->abort();
            return;
        }
        $context->json([
            "Ok" => true,
//            "Res" => $res,
//            "Count" => $count,
//            "First" => $first,
            "Exists" => $exists
        ]);
    }

    #[Path("/say_hello")]
    public function say_hello(Context $context): void
    {
        $context->json([
            "msg" => "hello"
        ]);
    }
}
