<?php

namespace Watish\WatishWEB\Command;

use Watish\Components\Attribute\Command;
use Watish\Components\Constructor\ClassLoaderConstructor;
use Watish\Components\Utils\Logger;

#[Command("generate","cache")]
class GenerateCache implements CommandInterface
{
    public function handle(): void
    {
        $resArray = [];
        foreach (ClassLoaderConstructor::getClassLoaderList() as $classLoader)
        {
            $resArray = array_merge($resArray,$classLoader->getClasses());
        }
        Logger::debug($resArray);
    }

}
