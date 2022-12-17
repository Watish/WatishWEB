<?php

namespace Watish\Components\Attribute;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Command
{
    public string $command;
    public string $prefix;

    public function __construct(string $command , string $prefix="command")
    {
        $this->command = $command;
        $this->prefix = $prefix;
    }
}
