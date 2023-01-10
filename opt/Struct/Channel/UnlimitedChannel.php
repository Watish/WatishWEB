<?php

namespace Watish\Components\Struct\Channel;

use Swoole\Coroutine;

class UnlimitedChannel
{
    private array $channelHash = [];
    private array $waitHash = [];

    public function push(mixed $data) :void
    {
        $need_resume = false;
        if(empty($this->channelHash))
        {
            $yield_cid = array_pop($this->waitHash);
            $need_resume = (bool)$yield_cid;
        }
        array_unshift($this->channelHash,$data);
//        $this->channelHash[] = $data;
        if($need_resume)
        {
            Coroutine::resume($yield_cid);
        }
    }

    public function pop()
    {
        $cid = Coroutine::getCid();
        if(empty($this->channelHash))
        {
            $this->waitHash[] = $cid;
            Coroutine::yield();
        }
        return array_pop($this->channelHash);
    }

    public function isEmpty() :bool
    {
        return empty($this->channelHash);
    }

    public function length() :int
    {
        return isset($this->channelHash) ? count($this->channelHash) : 0;
    }

    public function close(): void
    {
        $this->channelHash = [];
        if(isset($this->waitHash))
        {
            foreach ($this->waitHash as $cid)
            {
                Coroutine::resume($cid);
            }
        }
    }
}
