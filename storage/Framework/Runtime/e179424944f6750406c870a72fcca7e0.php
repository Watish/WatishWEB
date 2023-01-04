<?php



namespace Watish\WatishWEB\Aspect;



use Watish\Components\Utils\Aspect\AspectContainer;



class PROXY_8b9b61672803576_TestAspect implements AspectInterface

{

    public function handle(array $params): string

    {

        AspectContainer::continue();

        return "Proxy Hello World";

    }



}

