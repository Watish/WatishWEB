<?php

namespace Watish\Components\Constructor;

use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

class LocalFilesystemConstructor
{

    private static Filesystem $filesystem;
    private static FilesystemAdapter $illuminate_filesystem;
    private static Filesystem $root_filesystem;
    private static FilesystemAdapter $root_illuminate_filesystem;
    private static Filesystem $public_filesystem;
    private static FilesystemAdapter $public_illuminate_filesystem;

    public static function init(): void
    {
        $adapter = new LocalFilesystemAdapter(
        // Determine root directory
            BASE_DIR
        );
        $root_adapter = new LocalFilesystemAdapter(
        // Determine root directory
            '/'
        );
        $public_adapter = new LocalFilesystemAdapter(
            // BASE_DIR.'public/'
            '/'
        );
        $filesystem = new Filesystem($adapter);
        self::$illuminate_filesystem = new FilesystemAdapter($filesystem,$adapter,[]);
        self::$filesystem = $filesystem;
        self::$root_filesystem = new Filesystem($root_adapter);
        self::$public_filesystem = new Filesystem($public_adapter);
        self::$root_illuminate_filesystem = new FilesystemAdapter(self::$root_filesystem,$adapter,[]);
        self::$public_illuminate_filesystem = new FilesystemAdapter(self::$public_filesystem,$public_adapter,[]);
    }

    /**
     * @return Filesystem
     */
    public static function getFilesystem(): Filesystem
    {
        return self::$filesystem;
    }

    /**
     * @return Filesystem
     */
    public static function getPublicFilesystem(): Filesystem
    {
        return self::$public_filesystem;
    }

    /**
     * @return FilesystemAdapter
     */
    public static function getIlluminateFilesystem(): FilesystemAdapter
    {
        return self::$illuminate_filesystem;
    }

    /**
     * @return Filesystem
     */
    public static function getRootFilesystem(): Filesystem
    {
        return self::$root_filesystem;
    }

    /**
     * @return FilesystemAdapter
     */
    public static function getRootIlluminateFilesystem(): FilesystemAdapter
    {
        return self::$root_illuminate_filesystem;
    }

    public static function getPublicIlluminateFilesystem(): FilesystemAdapter
    {
        return self::$public_illuminate_filesystem;
    }
}
