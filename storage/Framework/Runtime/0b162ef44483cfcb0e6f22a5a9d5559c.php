<?php



namespace Watish\WatishWEB\Task;



use Watish\Components\Attribute\Crontab;



#[Crontab("* * * * *")]

class PROXY_8abe61671462743_HelloTask implements TaskInterface

{

    public function execute(): void

    {

//        Logger::debug("Hello","HelloTask");

    }

}

