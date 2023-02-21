<?php

namespace Watish\Components\Kernel\Process;

use Swoole\Coroutine;
use Watish\Components\Struct\Channel\UnlimitedStaticChannel;
use Watish\Components\Utils\Process\Messenger;
use Watish\Components\Utils\ProcessSignal;
use Watish\WatishWEB\Process\ProcessInterface;

class TaskProcess implements ProcessInterface
{
    public function execute(\Swoole\Process $process, Messenger $messenger): void
    {
        $socket = $process->exportSocket();
        $socket->send(ProcessSignal::SendMsg("Task Process Started!"));
        Coroutine::create(function () use ($socket,$messenger) {
            Coroutine::create(function () use ($socket,$messenger){
                while (1) {
                    $receive = $messenger->recv();
                    if (!$receive) {
                        Coroutine::sleep(CPU_SLEEP_TIME);
                        continue;
                    }
                    $receive = ProcessSignal::Parse($receive);
                    if ($receive["TYPE"] == "AsyncTask") {
                        UnlimitedStaticChannel::push("AsyncTask",$receive);
                    }
                }
            });
            Coroutine::create(function (){
                while (1)
                {
                    $receive = UnlimitedStaticChannel::pop("AsyncTask");
                    Coroutine::create(function () use ($receive) {
                        $closure = @unserialize($receive["CLOSURE"]);
                        $closure();
                    });
                }
            });

        });
    }
}
