<?php

namespace Watish\Components\Attribute;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Inject
{
    public string $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }

}
