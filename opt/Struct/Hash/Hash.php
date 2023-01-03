<?php

namespace Watish\Components\Struct\Hash;

class Hash
{
    private array $data = [];
    private int $num = 0;

    public function set(string $key,mixed $data): void
    {
        $this->data[$key] = $data;
        $this->num++;
    }

    public function exists(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function get(string $key)
    {
        if (!isset($this->data[$key]))
        {
            return null;
        }
        return $this->data[$key];
    }

    public function count(): int
    {
        return $this->num;
    }

    public function clear(): void
    {
        if($this->num<=0)
        {
            return;
        }
        $this->num = 0;
        $this->data = [];
    }

    public function del(string $key): void
    {
        if(!isset($this->data[$key]))
        {
            return;
        }
        $this->num--;
        unset($this->data[$key]);
    }

    public function isEmpty() :bool
    {
        return ($this->num <= 0);
    }

    public function keys() :array
    {
        return array_keys($this->data);
    }

}
