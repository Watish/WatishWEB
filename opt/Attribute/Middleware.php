<?php

namespace Watish\Components\Attribute;

use Attribute;

#[Attribute]
class Middleware
{
    public array $middlewares;
    public function __construct(array $middlewares)
    {
        $this->middlewares = $middlewares;
    }
}
