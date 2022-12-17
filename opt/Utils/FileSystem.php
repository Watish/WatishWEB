<?php

namespace Watish\Components\Utils;

use League\Flysystem\Local\LocalFilesystemAdapter;

class FileSystem
{
    public static function root(): \League\Flysystem\Filesystem
    {
        $adapter = new LocalFilesystemAdapter(
        // Determine root directory
            BASE_DIR
        );
        return new \League\Flysystem\Filesystem($adapter);
    }

    public static function storage(): \League\Flysystem\Filesystem
    {
        $adapter = new LocalFilesystemAdapter(BASE_DIR."data/");
        return new \League\Flysystem\Filesystem($adapter);
    }

}
