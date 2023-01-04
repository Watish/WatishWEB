<?php

namespace Watish\Components\Constructor;

use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\FileEngine;
use Illuminate\View\View;
use Watish\Components\Utils\Logger;

class ViewConstructor
{
    private static CompilerEngine $engine;
    private static \Illuminate\View\Factory $factory;
    private static string $view_path;
    private static bool $init = false;

    public static function  init(): void
    {
        if(self::$init)
        {
            return;
        }
        $engine_resolver = new \Illuminate\View\Engines\EngineResolver();
        $illuminate_filesystem = new \Illuminate\Filesystem\Filesystem();
        self::$view_path = BASE_DIR.'storage/View/';
        $finder = new \Illuminate\View\FileViewFinder(
            $illuminate_filesystem,
            [BASE_DIR]
        );
        $dispatcher = new \Illuminate\Events\Dispatcher();
        $factory = new \Illuminate\View\Factory($engine_resolver,$finder,$dispatcher);
        $compiler = new BladeCompiler($illuminate_filesystem,BASE_DIR.'/storage/Framework/ViewCache/');
        $engine = new CompilerEngine($compiler,$illuminate_filesystem);
        self::$engine = $engine;
        self::$factory = $factory;
        self::$init = true;
    }

    public static function render(string $view ,array $data=[]): string
    {
        if(!self::$init)
        {
            self::init();
        }
        $view = new View(self::$factory, self::$engine, $view, self::$view_path.$view.".blade.php", $data);
        try{
            return $view->render();
        }catch (\Exception $exception)
        {
            Logger::exception($exception);
            return $exception->getMessage();
        }

    }

    public static function view(string $view,array $data=[]): View
    {
        if(!self::$init)
        {
            self::init();
        }
        return new View(self::$factory,self::$engine,$view,self::$view_path.$view.".blade.php",$data);
    }
}
