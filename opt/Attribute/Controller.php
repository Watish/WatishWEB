<?php

namespace Watish\Components\Attribute;

use Attribute;

#[Attribute]
class Controller
{
    public string $prefix;

    public function __construct(string $prefix)
    {
        $this->$prefix = $prefix;
    }
}
