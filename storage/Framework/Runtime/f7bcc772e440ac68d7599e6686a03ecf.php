<?php



namespace Watish\WatishWEB\Command;



use Watish\Components\Attribute\Command;

use Watish\Components\Utils\Logger;



#[Command("hello","command")]

class PROXY_97cfd1672803576_HelloCommand implements CommandInterface

{

    public function handle(): void

    {

        Logger::info("Hello");

    }



}

