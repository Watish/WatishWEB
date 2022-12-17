<?php

namespace Watish\Components\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Aspect
{
    public string $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }
}
