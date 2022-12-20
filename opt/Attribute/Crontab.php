<?php

namespace Watish\Components\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Crontab
{
    public string $rule;

    public function __construct(string $rule="* * * * *")
    {
        $this->rule = $rule;
    }
}
