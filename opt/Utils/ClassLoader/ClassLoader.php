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
    private array $proxy_keywords_set = [];
    private array $proxy_set = [];

    /**
     * @throws FilesystemException
     */
    public function __construct(string $dir_path, string $namespace, bool $deep = true)
    {
        $filesystem = FileSystem::root();
        $this->filesystem = $filesystem;
        $this->proxy = true;
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
                $proxy_class = $translator->getProxyClassName();
                $this->proxy_keywords_set[$proxy_class] = $class;
            }
        }
        foreach (get_declared_classes() as $class)
        {
            if(str_contains($class,"PROXY_"))
            {
                foreach (array_keys($this->proxy_keywords_set) as $keyword)
                {
                    if(str_contains($class,$keyword))
                    {
                        $origin_class = $this->proxy_keywords_set[$keyword];
                        $this->proxy_set[$origin_class] = $class;
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getClasses():array
    {
        return $this->classes;
    }

    public function isProxy() :bool
    {
        return $this->proxy;
    }

    /**
     * @return array
     */
    public function getProxySet(): array
    {
        return $this->proxy_set;
    }

    /**
     * @return array
     */
    public function getPathList(): array
    {
        return $this->pathes;
    }
}
