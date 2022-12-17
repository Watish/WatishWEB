<?php

namespace Watish\Components\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class GlobalMiddleware
{
    public function __construct()
    {
    }
}
