<?php

namespace Watish\Components\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Event
{
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}