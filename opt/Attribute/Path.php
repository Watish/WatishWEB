<?php

namespace Watish\Components\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Path
{
    public string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }
}
