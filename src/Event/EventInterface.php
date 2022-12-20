<?php

namespace Watish\WatishWEB\Event;

use Watish\Components\Includes\Context;

interface EventInterface
{
    public function trigger(): void ;
}
