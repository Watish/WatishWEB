<?php

namespace Watish\Components\Struct\Set;

class LimitSet
{
    private int $size;
    private int $num;
    private array $set;

    public function __construct(int $size)
    {
        $this->size = $size;
        $this->set = [];
        $this->num = 0;
    }

    public function set(string $key) :bool
    {
        if($this->num >= $this->size)
        {
            return false;
        }
        $this->num++;
        $this->set[$key] = 1;
        return true;
    }

    public function isFull(): bool
    {
        return ($this->num == $this->size);
    }

    public function isEmpty() :bool
    {
        return ($this->num <= 0);
    }

    public function count(): int
    {
        return $this->num;
    }

    public function del(string $key) :void
    {
        if(!isset($this->set[$key]))
        {
            return;
        }
        unset($this->set[$key]);
        $this->num--;
    }

    public function exists(string $key) :bool
    {
        return isset($this->set[$key]);
    }

    public function get(string $key)
    {
        if(!isset($this->set[$key]))
        {
            return null;
        }
        return $this->set[$key];
    }

    public function all(): array
    {
        return array_keys($this->set);
    }
}
