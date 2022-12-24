<?php

namespace Watish\WatishWEB\Controller;

use Watish\Components\Attribute\Path;
use Watish\Components\Struct\Request;

class HelloController
{
    #[Path('/')]
    public function index(Request $request) :array
    {
        return [
            "msg" => "hello world"
        ];
    }
}
