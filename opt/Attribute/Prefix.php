<?php

namespace Watish\Components\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Prefix
{
    public string $prefix;
    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }
}
