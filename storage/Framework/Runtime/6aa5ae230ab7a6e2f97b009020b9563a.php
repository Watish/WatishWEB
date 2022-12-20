<?php



namespace Watish\WatishWEB\Aspect;



use Watish\Components\Utils\Aspect\AspectContainer;



class PROXY_89ea81671462743_TestAspect implements AspectInterface

{

    public function handle(array $params): string

    {

        AspectContainer::continue();

        return "Proxy Hello World";

    }



}

