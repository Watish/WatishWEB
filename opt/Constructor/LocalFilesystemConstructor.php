<?php

namespace Watish\Components\Constructor;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

class LocalFilesystemConstructor
{

    private static Filesystem $filesystem;

    public static function init(): void
    {
        $adapter = new LocalFilesystemAdapter(
        // Determine root directory
            BASE_DIR
        );
        $filesystem = new Filesystem($adapter);
        self::$filesystem = $filesystem;
    }

    /**
     * @return Filesystem
     */
    public static function getFilesystem(): Filesystem
    {
        return self::$filesystem;
    }
}
