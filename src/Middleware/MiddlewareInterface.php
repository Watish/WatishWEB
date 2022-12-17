<?php

namespace Watish\WatishWEB\Middleware;

use Watish\Components\Includes\Context;

interface MiddlewareInterface
{
    public function handle(Context $context);
}
