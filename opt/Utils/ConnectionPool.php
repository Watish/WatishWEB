<?php

namespace Watish\Components\Utils;

use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

class ConnectionPool
{
    private int $max_count;
    private int $min_count;
    private mixed $callback;
    private Channel $channel;
    private int $client_num;
    private bool $lock = false;

    public function __construct(callable $callback , int $max_count , int $min_count )
    {
        $this->callback = $callback;
        $this->max_count = $max_count;
        $this->min_count = $min_count;
        $this->client_num = 0;
        $this->channel = new Channel($max_count);
    }

    public function startPool():void
    {
        Coroutine::create(function (){
            for($i=0;$i<$this->min_count;$i++)
            {
                $this->make();
            }
        });
    }

    public function get():mixed
    {
        if($this->client_num <= 0)
        {
            $this->make();
        }
        $res = $this->channel->pop();
        $this->client_num--;
        return $res;
    }

    public function put($client): void
    {
        if(is_null($client))
        {
            return;
        }
        if($this->client_num >= $this->max_count)
        {
            return;
        }
        $this->client_num++;
        $this->channel->push($client);
    }

    public function stats() :array
    {
        return [
            "max_pool_limit" => $this->max_count,
            "min_pool_limit" => $this->min_count,
            "client_count" => $this->client_num
        ];
    }

    public function fill() :void
    {
        $left = $this->max_count - $this->client_num;
        if($left <= 0)
        {
            return;
        }
        for($i=0;$i<$left;$i++)
        {
            $this->make();
        }
    }

    private function make(): void
    {
        if($this->client_num >= $this->max_count)
        {
            return;
        }
        $client = ($this->callback)();
        if(!$client)
        {
            return;
        }
        $this->channel->push($client);
        $this->client_num++;
    }
}
