<?php

namespace Watish\WatishWEB\Controller\Http;

use Watish\Components\Attribute\Inject;
use Watish\Components\Attribute\Path;
use Watish\Components\Attribute\Prefix;
use Watish\Components\Includes\Context;
use Watish\Components\Utils\Table;
use Watish\WatishWEB\Service\TestService;

#[Prefix("/")]
class IndexController
{
    #[Inject(TestService::class)]
    private TestService  $testService;

    #[Path("",['GET','POST'])]
    public function index(Context $context): array
    {
        $request = $context->getRequest();
        return [
            "Ok" => true,
            "Msg" => $request->getMethod()
        ];
    }
}
