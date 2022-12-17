<?php

namespace Watish\Components\Utils;

use Opis\Closure\SerializableClosure;

class ProcessSignal
{
    public static function TriggerEvent(string $event):string
    {
        return json_encode([
            "TYPE" => "TRIGGER",
            "EVENT" => $event
        ]);
    }

    public static function SendMsg(string $msg):string
    {
        return json_encode([
            "TYPE" => "MSG",
            "MSG" =>  $msg
        ]);
    }

    public static function Parse(string $str):array
    {
        return json_decode($str,true);
    }

    public static function AsyncTask(\Closure $closure): string
    {
        $wrapper = @new SerializableClosure($closure);
        return json_encode([
            "TYPE" => "AsyncTask",
            "CLOSURE" => @serialize($wrapper)
        ]);
    }

}
