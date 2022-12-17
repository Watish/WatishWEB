<?php

namespace Watish\WatishWEB\Aspect;

use Watish\Components\Utils\Aspect\AspectContainer;

class TestAspect implements AspectInterface
{
    public function handle(array $params): string
    {
        AspectContainer::continue();
        return "Proxy Hello World";
    }

}
