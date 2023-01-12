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
    private bool $started = false;
    private int $live_time;
    private int $qps = 0;
    private string $name;

    public function __construct(callable $callback , int $max_count , int $min_count ,int $live_time = 360,string $name="connection_pool" )
    {
        $this->callback = $callback;
        $this->max_count = $max_count;
        $this->min_count = $min_count;
        $this->client_num = 0;
        $this->live_time = $live_time;
        $this->name = $name;
    }

    public function startPool():void
    {
        $this->started = true;
        $this->channel = new Channel($this->max_count);
        Coroutine::create(function (){
            for($i=0;$i<$this->min_count;$i++)
            {
                $this->make();
            }
        });
        self::watching();
    }

    public function stopPool():void
    {
        $this->started = false;
        $this->channel->close();
    }

    public function get():mixed
    {
        if(!$this->started)
        {
            return $this->getClient();
        }
        $this->qps++;
        if($this->client_num <= 0)
        {
            return $this->getClient();
        }
        $this->client_num--;
        $res = $this->channel->pop();
        if(time() - $res["time"] > $this->live_time)
        {
            return $this->getClient();
        }
        return $res["client"];
    }

    public function put($client): void
    {
        if(!$this->started)
        {
            return;
        }
        if(is_null($client))
        {
            return;
        }
        if($this->client_num >= $this->max_count)
        {
            unset($client);
            return;
        }
        $this->client_num++;
        $this->channel->push([
            "client" => $client,
            "time" => time()
        ]);
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

    private function getClient() :mixed
    {
        return ($this->callback)();
    }

    private function make(): void
    {
        if(!$this->started)
        {
            return;
        }
        if($this->channel->isFull())
        {
            return;
        }
        if($this->client_num >= $this->max_count)
        {
            return;
        }
        $client = $this->getClient();
        if(!$client)
        {
            return;
        }
        $this->client_num++;
        $this->channel->push([
            "client" => $client,
            "time" => time()
        ]);
    }

    public function watching() :void
    {
        Logger::debug("Watching","ConnectionPool");
        Coroutine::create(function (){
            while(1)
            {
                Coroutine::sleep(1);
                $qps = $this->qps;
                $this->qps = 0;
                if($qps > $this->min_count)
                {
                    // qps > client_num >= min_count
                    if($this->client_num <= $this->min_count)
                    {
                        if($qps > $this->max_count)
                        {
                            Logger::debug("{$this->name} qps:{$qps}",$this->name);
                        }

                        Logger::debug("{$this->name} fill",$this->name);
                        while(1)
                        {
                            if($this->channel->isFull())
                            {
                                break;
                            }
                            if($this->client_num >= $qps)
                            {
                                break;
                            }
                            $this->make();
                        }
                    }
                }else{
                    // client_num > min_count > qps
                    if($this->client_num > $this->min_count)
                    {
                        Logger::debug("{$this->name} remove",$this->name);
                        while(1)
                        {
                            if($this->client_num<=$this->min_count)
                            {
                                break;
                            }
                            $client = $this->get();
                            unset($client);
                        }
                    }
                }
            }
        });
    }
}
