<?php

namespace Watish\Components\Attribute;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Process
{
    public string $name;
    public int $worker_num;

    public function __construct(string $name, int $worker_num = 1)
    {
        $this->name = $name;
        $this->worker_num = $worker_num;
    }

}
