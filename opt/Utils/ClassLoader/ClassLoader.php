<?php

namespace Watish\Components\Utils\ClassLoader;

use League\Flysystem\FilesystemException;
use League\Flysystem\StorageAttributes;
use Watish\Components\Utils\FileSystem;

class ClassLoader
{
    private \League\Flysystem\Filesystem $filesystem;
    private array $classes = [];
    private array $pathes = [];

    /**
     * @throws FilesystemException
     */
    public function __construct(string $dir_path, string $namespace, bool $deep = true)
    {
        $filesystem = FileSystem::root();
        $this->filesystem = $filesystem;
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

    /**
     * @return array
     */
    public function getPathes(): array
    {
        return $this->pathes;
    }
}
