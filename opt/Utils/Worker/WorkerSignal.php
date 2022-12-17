<?php

namespace Watish\Components\Utils\Worker;

class WorkerSignal
{
    public static function KV_Set(string $key,mixed $data) :string
    {
        return json_encode([
            "TYPE" => "KV_SET",
            "KEY" => $key,
            "DATA" => $data
        ]);
    }

    public static function KV_Del(string $key) :string
    {
        return json_encode([
           "TYPE" => "KV_DEL",
           "KEY" => $key,
        ]);
    }

    public static function Set_Add(string $key,string $uuid,mixed $data) :string
    {
        return json_encode([
           "TYPE" => "SET_ADD",
            "KEY" => $key,
            "UUID" => $uuid,
            "DATA" => $data
        ]);
    }

    public static function Set_Del(string $key,string $uuid) :string
    {
        return json_encode([
            "TYPE" => "SET_DEL",
            "KEY" => $key,
            "UUID" => $uuid
        ]);
    }

    public static function Set_Push_All(string $key,string  $msg):string
    {
        return json_encode([
           "TYPE" => "SET_PUSH_ALL",
           "KEY" => $key,
           "MSG" => $msg
        ]);
    }

    public static function Set_Push(string $key,string $uuid,string $msg):string
    {
        return json_encode([
           "TYPE" => "SET_PUSH",
           "KEY" => $key,
           "UUID" => $uuid,
           "MSG" => $msg
        ]);
    }

    public static function KV_Push(string $key,string $msg):string
    {
        return json_encode([
            "TYPE" => "KV_PUSH",
            "KEY" => $key,
            "MSG" => $msg
        ]);
    }

}
