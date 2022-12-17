<?php

namespace Watish\Components\Utils;

use League\CLImate\CLImate;

class Logger
{
    /**
     * @var false
     */
    private static bool $debug_mode;

    public static function info(string|array|object $data, $prefix=null): void
    {
        $cli_mate = new CLImate();
        $prefix = self::prefix($prefix,"INFO");
        $data = self::convert_data_to_string($data);
        $msg = $prefix.$data;
        $cli_mate->blink()->dim($msg);
    }

    public static function debug(string|array|object $data,string $prefix=null):void
    {
        if(!isset(self::$debug_mode))
        {
            self::$debug_mode = Table::get("server_config")["debug_mode"];
        }

        if(!self::$debug_mode)
        {
            return;
        }

        $cli_mate = new CLImate();
        $prefix = self::prefix($prefix,'DEBUG');
        $data = self::convert_data_to_string($data);
        $msg = $prefix.$data;
        $cli_mate->green($msg);
    }

    public static function error(string|array|object $data,string $prefix=null):void
    {
        $cli_mate = new CLImate();
        $prefix = self::prefix($prefix,"ERROR");
        $data = self::convert_data_to_string($data);
        $msg = $prefix.$data;
        $cli_mate->bold()->red($msg);
    }

    public static function warn(string|array|object $data,$prefix=null):void
    {
        $cli_mate = new CLImate();
        $prefix = self::prefix($prefix,"WARN");
        $data = self::convert_data_to_string($data);
        $msg = $prefix.$data;
        $cli_mate->bold()->yellow($msg);
    }

    public static function exception(\Exception $exception) :void
    {
        $listArray = [];
        $listArray["msg"] = $exception->getMessage();
        $listArray["code"] = $exception->getCode();
        $listArray["file"] = $exception->getFile();
        $listArray["line"] = $exception->getLine();
        $listArray["strace"] = $exception->getTraceAsString();

        $climate = self::CLImate();
        foreach ($listArray as $name => $text)
        {
            $climate->red("[Exception][{$name}]:{$text}");
        }
    }

    public static function table(array $data) :void
    {
        $cli_mate = new CLImate();
        $cli_mate->table($data);
    }

    public static function clear():void
    {
        $cli_mate = new CLImate();
        $cli_mate->clear();
    }

    public static function CLImate(): CLImate
    {
        return new CLImate();
    }

    private static function convert_data_to_string(mixed $data) :string
    {
        if(is_object($data))
        {
            return json_encode($data);
        }
        if(is_array($data))
        {
            return json_encode($data);
        }
        if(is_numeric($data))
        {
            return (string)$data;
        }
        if(is_string($data))
        {
            return $data;
        }
        try {
            $msg = (string)$data;
        }catch (\Exception $e)
        {
            $msg = json_encode($data);
        }
        return $msg;
    }

    private static function prefix(string $level=null,string $default): string
    {
        if(!$level)
        {
            $prefix = $default;
        }else{
            $prefix = $level;
        }
        $date = date("Y-m-d H:i:s");
        return "[{$date}][$prefix]:";
    }
}
