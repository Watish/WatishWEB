<?php

namespace Watish\Components\Utils\ClassLoader;

use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use Watish\Components\Utils\FileSystem;
use Watish\Components\Utils\ClassLoader\ClassTranslator;
use Watish\Components\Utils\Logger;

class ClassLoader
{
    private \League\Flysystem\Filesystem $filesystem;
    private array $classes = [];
    private array $pathes = [];
    private bool $proxy;
    private array $proxy_class = [];

    /**
     * @throws FilesystemException
     */
    public function __construct(string $dir_path, string $namespace, bool $deep = true , bool $proxy = false)
    {
        $filesystem = FileSystem::root();
        $this->filesystem = $filesystem;
        $this->proxy = $proxy;
        $controllers = $this->filesystem->listContents($dir_path,$deep)->filter(function (StorageAttributes $attributes) {
            return $attributes->isFile();
        })->toArray();
        foreach ($controllers as $controller)
        {
            $controller_path = $controller["path"];
            $this->pathes[] = $controller_path;
            require_once BASE_DIR .$controller_path;
        }
        foreach (get_declared_classes() as $class)
        {
            if(str_starts_with($class, $namespace))
            {
                $this->classes[] = $class;
                $translator = new ClassTranslator($class);
                $translator->translate();
                $translator->save_and_require();
            }
        }
        if($proxy)
        {
            foreach (get_declared_classes() as $class)
            {
                if(str_contains($class,"PROXY_"))
                {
                    Logger::debug($class);
                    $this->proxy_class[] = $class;
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getClasses():array
    {
        if($this->proxy)
        {
            return $this->proxy_class;
        }
        return $this->classes;
    }

    /**
     * @return array
     */
    public function getPathList(): array
    {
        return $this->pathes;
    }
}
