<?php



namespace Watish\WatishWEB\Command;



use Watish\Components\Attribute\Command;

use Watish\Components\Utils\Logger;



#[Command("hello","command")]

class PROXY_8b02a1671462743_HelloCommand implements CommandInterface

{

    public function handle(): void

    {

        Logger::info("Hello");

    }



}

