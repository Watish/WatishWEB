<?php

namespace Watish\Components\Kernel\Process;

use Swoole\Coroutine;
use Watish\Components\Struct\Channel\UnlimitedStaticChannel;
use Watish\Components\Utils\Process\Messager;
use Watish\Components\Utils\ProcessSignal;
use Watish\WatishWEB\Process\ProcessInterface;

class TaskProcess implements ProcessInterface
{
    public function execute(\Swoole\Process $process,Messager $messager): void
    {
        $socket = $process->exportSocket();
        $socket->send(ProcessSignal::SendMsg("Task Process Started!"));
        Coroutine::create(function () use ($socket,$messager) {
            Coroutine::create(function () use ($socket,$messager){
                while (1) {
                    $receive = $messager->recv();
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
