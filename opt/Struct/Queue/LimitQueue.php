<?php

namespace Watish\Components\Struct;

class LimitQueue
{
    private array $keys_queue;
    private array $keys_set;
    private int $queue_length;
    private int $queue_num;

    public function __construct(int $queue_length)
    {
        $this->keys_queue = [];
        $this->keys_set = [];
        $this->queue_length = $queue_length;
        $this->queue_num = 0;
    }

    public function pop() : string|null
    {
        if($this->queue_num<=0)
        {
            return null;
        }
        $key = array_shift($this->keys_queue);
        $this->queue_num--;
        if(!in_array($key,$this->keys_queue))
        {
            unset($this->keys_set[$key]);
        }
        return $key;
    }

    public function isFull() :bool
    {
        return ($this->queue_num >= $this->queue_length);
    }

    public function isEmpty(): bool
    {
        return ($this->queue_num <= 0);
    }

    public function count(): int
    {
        return $this->queue_num;
    }

    public function push(string $key) :null|string
    {
        $res = null;
        if($this->queue_num >= $this->queue_length and $this->queue_length>0)
        {
            //Full
            $res = $this->pop();
        }
        $this->keys_queue[] = $key;
        $this->keys_set[$key] = null;
        $this->queue_num++;

        return $res;
    }

    public function exists(string $key) :bool
    {
        return isset($this->keys_set[$key]);
    }

    public function getList(): array
    {
        return $this->keys_queue;
    }

}
