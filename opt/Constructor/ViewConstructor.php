<?php

namespace Watish\Components\Constructor;

use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Engines\FileEngine;
use Illuminate\View\Engines\PhpEngine;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\View\View;
use Watish\Components\Utils\Logger;

class ViewConstructor
{
    private static CompilerEngine $engine;
    private static Factory $factory;
    private static string $view_path;
    private static bool $init = false;

    public static function  init(): void
    {
        if(self::$init)
        {
            return;
        }

        $illuminate_filesystem = new Filesystem();
        $compiler = new BladeCompiler($illuminate_filesystem,CACHE_PATH.'/ViewCache/');
        $engine_resolver = new EngineResolver;
        $engine_resolver->register("blade",function () use ($compiler,$illuminate_filesystem){
            return new CompilerEngine($compiler,$illuminate_filesystem);
        });
        $engine_resolver->register("php",function () use ($compiler,$illuminate_filesystem){
            return new PhpEngine($illuminate_filesystem);
        });

        self::$view_path = BASE_DIR.'/storage/View/';
        $finder = new FileViewFinder(
            $illuminate_filesystem,
            [BASE_DIR.'/storage/View/']
        );
        $dispatcher = new Dispatcher();
        $factory = new Factory($engine_resolver,$finder,$dispatcher);

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
        $view = self::$factory->make($view,$data);
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
