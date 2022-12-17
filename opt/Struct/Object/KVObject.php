<?php

namespace Watish\Components\Struct\Object;

class KVObject
{
    public string $key;
    public mixed $value;

    public function __construct(string $key , mixed $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
}
