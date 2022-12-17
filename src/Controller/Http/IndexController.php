<?php

namespace Watish\WatishWEB\Controller\Http;

use Watish\Components\Attribute\Inject;
use Watish\Components\Attribute\Path;
use Watish\Components\Attribute\Prefix;
use Watish\Components\Includes\Context;
use Watish\WatishWEB\Service\TestService;

#[Prefix("/")]
class IndexController
{
    #[Inject(TestService::class)]
    private TestService $testService;

    #[Path("")]
    public function index(Context $context): void
    {
        $context->json([
            "Ok" => true,
            "Msg" => $this->testService->sayHello()
        ]);
    }
}
