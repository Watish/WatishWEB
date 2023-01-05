<?php

namespace Watish\WatishWEB\Process;

use Swoole\Coroutine;
use Watish\Components\Struct\Channel\UnlimitedChannel;
use Watish\Components\Utils\ProcessSignal;

class TaskProcess implements ProcessInterface
{
    public function execute(\Swoole\Process $process): void
    {
        $socket = $process->exportSocket();
        $socket->send(ProcessSignal::SendMsg("Task Process Started!"));
        Coroutine::create(function () use ($socket) {
            Coroutine::create(function () use ($socket){
                while (1) {
                    $receive = $socket->recv();
                    if (!$receive) {
                        continue;
                    }
                    $receive = ProcessSignal::Parse($receive);
                    if ($receive["TYPE"] == "AsyncTask") {
                        UnlimitedChannel::push("AsyncTask",$receive);
                    }
                }
            });
            Coroutine::create(function (){
                while (1)
                {
                    $receive = UnlimitedChannel::pop("AsyncTask");
                    Coroutine::create(function () use ($receive) {
                        $closure = @unserialize($receive["CLOSURE"]);
                        $closure();
                    });
                }
            });

        });
    }
}
