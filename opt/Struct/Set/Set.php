<?php

namespace Watish\Components\Struct\Set;

class Set
{
    private int $num = 0;
    private array $set = [];

    public function __construct()
    {
        $this->set = [];
        $this->num = 0;
    }

    public function set(string $key) :bool
    {
        $this->num++;
        $this->set[$key] = 1;
        return true;
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

    public function get(string $key) :string|null
    {
        if(!isset($this->set[$key]))
        {
            return null;
        }
        return $this->set[$key];
    }
}
