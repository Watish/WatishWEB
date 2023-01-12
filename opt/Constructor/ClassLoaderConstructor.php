<?php

namespace Watish\Components\Constructor;

use SebastianBergmann\Diff\Exception;
use Watish\Components\Utils\ClassLoader\ClassLoader;
use Watish\Components\Utils\Logger;
use Watish\Components\Utils\Table;

class ClassLoaderConstructor
{
    private static bool $init = false;
    private static array $class_loader_set = [];

    public static function init():void
    {
        if(self::$init)
        {
            return;
        }
        $server_config = Table::get("server_config");
        $class_loader_list = $server_config["class_loader"];
        $fileSystem = LocalFilesystemConstructor::getFilesystem();
        try{
            if($fileSystem->directoryExists(CACHE_PATH."/Runtime/"))
            {
                $fileSystem->deleteDirectory(CACHE_PATH."/Runtime/");
                $fileSystem->createDirectory(CACHE_PATH."/Runtime/");
            }else{
                $fileSystem->createDirectory(CACHE_PATH."/Runtime/");
            }
        }catch (\Exception $exception)
        {
            Logger::exception($exception);
        }

        foreach ($class_loader_list as $name => $item)
        {
            $dir = $item["dir"];
            $namespace = $item["namespace"];
            $deep = $item["deep"];
            $proxy = $item["proxy"] ?? false;
            try {
                self::$class_loader_set[$name] = new ClassLoader($dir,$namespace,$deep,$proxy);
            }catch (Exception $exception)
            {
                Logger::error($exception->getMessage());
            }
        }
        self::$init = true;
    }

    public static function checkInit():bool
    {
        return isset(self::$init);
    }

    public static function getClassLoader(string $name) :null|ClassLoader
    {
        if(!isset(self::$class_loader_set[$name]))
        {
            return null;
        }
        return self::$class_loader_set[$name];
    }

    /**
     * @return ClassLoader[]
     */
    public static function getClassLoaderList(): array
    {
        return array_values(self::$class_loader_set);
    }
}
