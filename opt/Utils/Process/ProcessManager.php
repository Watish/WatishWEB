<?php

namespace Watish\Components\Utils\Process;

use Swoole\Coroutine;
use Watish\Components\Utils\Lock\GlobalLock;
use Watish\Components\Utils\Table;

class ProcessManager
{
    public static function make(string $name): Messenger
    {
        $uuid = self::genUUID();
        if(Coroutine::getCid() > 0)
        {
            Coroutine::create(function () use ($name,$uuid){
                GlobalLock::lock("ProcessManager");
                $processHash = self::getProcessHash();
                $processHash[$name][] = $uuid;
                $uuidSet = self::getUUIDSet();
                $uuidSet[$uuid] = 1;
                Table::set("processHash",$processHash);
                Table::set("UUIDSet",$uuidSet);
                GlobalLock::unlock("ProcessManager");
            });
        }else{
            $processHash = self::getProcessHash();
            $processHash[$name][] = $uuid;
            $uuidSet = self::getUUIDSet();
            $uuidSet[$uuid] = 1;
            Table::set("processHash",$processHash);
            Table::set("UUIDSet",$uuidSet);
        }
        return new Messenger($uuid);
    }

    public static function check_uuid_exists(string $uuid) :bool
    {
        $uuidSet = self::getUUIDSet();
        return isset($uuidSet[$uuid]);
    }

    public static function get_messenger_by_name(string $name): Messenger|null
    {
        $processHash = self::getProcessHash();
        if(!isset($processHash[$name]))
        {
            return null;
        }
        $count = count($processHash[$name]);
        if(!$count)
        {
            return null;
        }
        $uuid = $processHash[$name][rand(0,$count-1)];
        return new Messenger($uuid);
    }

    public static function get_uuid_list_by_name(string $name) :array
    {
        $processHash = self::getProcessHash();
        if(!isset($processHash[$name]))
        {
            return [];
        }
        return $processHash[$name];
    }

    private static function getProcessHash() :array
    {
        if(Table::exists("processHash"))
        {
            $processHash = Table::get("processHash");
        }else{
            $processHash = [];
        }
        return $processHash;
    }

    private static function getUUIDSet()
    {
        if(Table::exists("UUIDSet"))
        {
            $UUIDSet = Table::get("UUIDSet");
        }else{
            $UUIDSet = [];
        }
        return $UUIDSet;
    }

    private static function genUUID(): string
    {
        return md5(uniqid().time().rand(1000,9999));
    }
}