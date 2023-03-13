<?php

namespace Watish\WatishWEB\Controller\Ws;

use Watish\Components\Attribute\Path;
use Watish\Components\Attribute\Prefix;
use Watish\Components\Includes\Context;
use Watish\Components\Struct\Request;
use Watish\Components\Struct\Response;
use Watish\Components\Utils\Agent\Messenger;
use Watish\Components\Utils\Agent\ResponseAgent;
use Watish\Components\Utils\Logger;

#[Prefix('/ws')]
class IndexController
{
    #[Path('/')]
    public function handle(Request $request,Response $response): void
    {
        $response = $response->response;
        $fd = $response->fd;
        $response->detach();
        $response = \Swoole\Http\Response::create($fd);
        if($response instanceof  \Swoole\Http\Response)
        {
            Logger::info("Yes");
            $response->end("Yes");
        }else{
            Logger::error("No");
        }
        return;
        $uuid = time().rand(1,999);
        $responseAgent = new ResponseAgent($response,$uuid);
        $responseAgent->onOpen(function (Messenger $messenger) use ($uuid){
            $messenger->sendTo($uuid,"Hello");
            $messenger->sendAll("{$uuid} come");
        });
        $responseAgent->onClose(function (Messenger $messenger) use ($uuid) {
            $messenger->sendAll("{$uuid} left");
        });
        $responseAgent->onMessage(function (Messenger $messenger,string $msg) use ($uuid){
            $messenger->sendAll("{$uuid} say $msg");
        });
        $responseAgent->onError(function (\Exception $exception){
            Logger::exception($exception);
        });
        $responseAgent->confirm();
        $responseAgent->useResponse(function (Response $response){
            $response->push("Hello");
        });
    }
}