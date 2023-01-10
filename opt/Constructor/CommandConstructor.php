<?php

namespace Watish\Components\Constructor;

use Exception;
use GetOpt\Command;
use Swoole\Coroutine;
use Watish\Components\Utils\AttributeLoader\AttributeLoader;
use Watish\Components\Utils\Injector\ClassInjector;
use Watish\Components\Utils\Logger;

class CommandConstructor
{
    private static array $cmdSet = [];
    private static \GetOpt\GetOpt $getOpt;

    public static function init(): void
    {
        $getOpt = new \GetOpt\GetOpt();
        $getOpt->addOption("h","help",\GetOpt\GetOpt::NO_ARGUMENT);
        self::$getOpt = $getOpt;
    }

    public static  function autoRegister() :void
    {
        $commandLoader = ClassLoaderConstructor::getClassLoader("command");
        $classes = $commandLoader->getClasses();
        $attributeLoader = new AttributeLoader($classes);
        $attributes = $attributeLoader->getClassAttributes(\Watish\Components\Attribute\Command::class);
        foreach ($attributes as $className => $item)
        {
            if($item["count"]<=0)
            {
                continue;
            }
            $params = $item["attributes"][0]["params"];
            $prefix = $params[1] ?? "command";
            $command = $params[0];
            self::registerCommand("{$prefix}:{$command}",[ClassInjector::getInjectedInstance($className),"handle"]);
        }
    }

    /**
     * @return \GetOpt\GetOpt
     */
    public static function getGetOpt(): \GetOpt\GetOpt
    {
        return self::$getOpt;
    }

    public static function handle(): void
    {
        try {
            self::$getOpt->process();
        }catch (Exception $exception)
        {
            Logger::exception($exception);
            echo self::$getOpt->getHelpText();
            exit;
        }

        $command = self::$getOpt->getCommand();

        if(is_null($command))
        {
            //Keeping starting server
            return;
        }

        $cmd_name = $command->getName();
        if(isset(self::$cmdSet[$cmd_name]))
        {
            $call_back = self::$cmdSet[$cmd_name];
            Coroutine::create(function () use ($call_back){
                Coroutine::set([
                    "enable_deadlock_check" => false
                ]);
                call_user_func_array($call_back,[]);
            });
        }

        exit;
    }

    public static function registerCommand(string $cmd,array $callback): void
    {
        if(!isset(self::$getOpt))
        {
            self::init();
        }
        self::$getOpt->addCommand(Command::create($cmd,""));
        self::$cmdSet[$cmd] = $callback;
    }
}
