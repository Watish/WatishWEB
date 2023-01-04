<?php

namespace Watish\Components\Constructor;

use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

class LocalFilesystemConstructor
{

    private static Filesystem $filesystem;
    private static FilesystemAdapter $illuminate_filesystem;

    public static function init(): void
    {
        $adapter = new LocalFilesystemAdapter(
        // Determine root directory
            BASE_DIR
        );
        $filesystem = new Filesystem($adapter);
        self::$illuminate_filesystem = new FilesystemAdapter($filesystem,$adapter,[]);
        self::$filesystem = $filesystem;
    }

    /**
     * @return Filesystem
     */
    public static function getFilesystem(): Filesystem
    {
        return self::$filesystem;
    }

    /**
     * @return FilesystemAdapter
     */
    public static function getIlluminateFilesystem(): FilesystemAdapter
    {
        return self::$illuminate_filesystem;
    }
}
