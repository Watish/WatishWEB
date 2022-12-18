<?php

namespace Watish\Components\Constructor;

use Watish\Components\Includes\Context;
use Watish\Components\Utils\Logger;
use Watish\Components\Utils\Table;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Run;

class WoopsConstructor
{
    private static Run $whoops;
    private static bool $debug;

    public static function init(): void
    {
        if(Table::get("server_config")["debug_mode"]) {
            self::$debug = true;
        }else{
            self::$debug = false;
        }
    }

    /**
     * @return Run
     */
    public static function getWhoops(): Run
    {
        if(!isset(self::$whoops))
        {
            self::init();
        }
        return self::$whoops;
    }

    public static function handle($e, Context &$context , string $prefix = null) :void
    {
        $whoops = new Run;
        $whoops->allowQuit(false);
        $whoops->writeToOutput(false);
        $whoops->pushHandler(new JsonResponseHandler());
        Logger::exception($e);
        if(self::$debug)
        {
            $json = $whoops->handleException($e);
            $context->json(json_decode($json),500);
        }
        $context->abort();
        $context->reset();
    }


}
