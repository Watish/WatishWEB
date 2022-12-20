<?php



namespace Watish\WatishWEB\Controller\Http;



use Watish\Components\Attribute\Inject;

use Watish\Components\Attribute\Path;

use Watish\Components\Attribute\Prefix;

use Watish\Components\Includes\Context;

use Watish\Components\Utils\Table;

use Watish\WatishWEB\Service\TestService;



#[Prefix("/")]

class PROXY_8984d1671462743_IndexController

{

    #[Path("")]

    public function index(Context $context): array

    {

        $request = $context->getRequest();

        return [

            "Method" => $request->getMethod(),

            "Params" => $request->all()

        ];

    }

}

