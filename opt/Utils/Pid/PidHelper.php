<?php

namespace Watish\Components\Utils\Pid;

use Illuminate\Filesystem\FilesystemAdapter;
use Swoole\Process;
use Watish\Components\Constructor\LocalFilesystemConstructor;
use Watish\Components\Utils\Logger;

class PidHelper
{
    private static function getFilesystem(): FilesystemAdapter
    {
        $fileSystem = LocalFilesystemConstructor::getRootIlluminateFilesystem();
        if(!$fileSystem->directoryExists(CACHE_PATH."/pid/"))
        {
            $fileSystem->makeDirectory(CACHE_PATH."/pid/");
        }
        return $fileSystem;
    }

    public static function add(string $name,int $pid): bool
    {
        $fileSystem = self::getFilesystem();
        $pidPath = CACHE_PATH."/pid/{$name}.pid";
//        Logger::info($pidPath);
        if($fileSystem->fileExists($pidPath))
        {
            $json = $fileSystem->read($pidPath);
            $pid_list = json_decode($json,true);
            if(!in_array($pid,$pid_list))
            {
                $pid_list[] = $pid;
            }
            $fileSystem->delete($pidPath);
            $fileSystem->write($pidPath,json_encode($pid_list));
        }else{
            try{
                $fileSystem->write($pidPath,json_encode([$pid]));
            }catch (\Exception $exception)
            {
                Logger::exception($exception);
                return false;
            }
        }
        return true;
    }

    public static function pidList() :array
    {
        $fileSystem = self::getFilesystem();
        $files = $fileSystem->files(CACHE_PATH."/pid/");
        $pid_set = [];
        foreach ($files as $file)
        {
            $json = $fileSystem->read($file);
            $pid_list = json_decode($json,true);
            foreach ($pid_list as $pid)
            {
                $pid_set[$pid] = 1;
            }
        }
        return array_keys($pid_set);
    }

    public static function getNameByPid(int $pid) :string|null
    {
        $fileSystem = self::getFilesystem();
        $nameList = self::getNameList();
        foreach ($nameList as $name)
        {
            $pidPath = CACHE_PATH."/pid/{$name}.pid";
            try{
                $json = $fileSystem->read($pidPath);
            }catch (\Exception $exception)
            {
                Logger::exception($exception);
                continue;
            }
            $pidList = json_decode($json,true);
            if(in_array($pid,$pidList))
            {
                return $name;
            }
        }
        return null;
    }

    public static function exists(string $name): bool
    {
        $fileSystem = self::getFilesystem();
        $pidPath = CACHE_PATH."/pid/{$name}.pid";
        return $fileSystem->fileExists($pidPath);
    }

    public static function killName(string $name): void
    {
        $fileSystem = self::getFilesystem();
        $pidPath = CACHE_PATH."/pid/{$name}.pid";
        if($fileSystem->fileExists($pidPath))
        {
            try{
                $json = $fileSystem->read($pidPath);
            }catch (\Exception $exception)
            {
                Logger::exception($exception);
                return;
            }
            foreach (json_decode($json,true) as $pid)
            {
                Process::kill($pid);
            }
            $fileSystem->delete($pidPath);
        }
    }

    public static function killAll() :void
    {
        foreach (self::getNameList() as $name)
        {
            self::killName($name);
        }
    }

    public static function getNameList() :array
    {
        $fileSystem = self::getFilesystem();
        $files = $fileSystem->files(CACHE_PATH."/pid/");
        $res = [];
        foreach ($files as $file)
        {
            $item = [];
            $file = explode('/',$file);
            if(count($file)>0)
            {
                $file = $file[count($file)-1];
            }
            preg_match('/^(.{1,})\.pid$/',$file,$item);
            if(isset($item[1]))
            {
                $res[] = $item[1];
            }
        }
        return $res;
    }
}