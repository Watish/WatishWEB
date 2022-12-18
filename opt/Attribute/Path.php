<?php

namespace Watish\Components\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Path
{
    public string $path;
    public array $methods;

    public function __construct(string $path,array $methods = [])
    {
        $this->path = $path;
        $this->methods = $methods;
    }
}
