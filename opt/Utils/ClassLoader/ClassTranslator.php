<?php

namespace Watish\Components\Utils\ClassLoader;

use Swoole\Coroutine;
use Watish\Components\Constructor\LocalFilesystemConstructor;
use Watish\Components\Utils\Logger;

class ClassTranslator
{
    private string $class_name;
    private string|false $filename;
    private int|false $start_line;
    private int|false $end_line;
    private string|false $code_content;
    private string $proxy_code_content = '';
    private string $short_name;
    private string $proxy_class_name;

    public function __construct(string $className)
    {
        $this->class_name = $className;
        $reflectionClass = new \ReflectionClass($className);
        $this->filename = $reflectionClass->getFileName();
        $this->start_line = $reflectionClass->getStartLine();
        $this->end_line = $reflectionClass->getEndLine();
        $this->short_name = $reflectionClass->getShortName();
        Logger::debug("File:{$this->filename},Start:{$this->start_line},End:{$this->end_line},Class:{$this->short_name}");
    }

    public function translate(): void
    {
        $resource = fopen($this->filename,"r");
        $line = 0;
        while(1)
        {
            $line++;
            $r = fgets($resource);
            if(!$r)
            {
                break;
            }
            if($line>=$this->start_line and $line <= $this->end_line)
            {
                if($line == $this->start_line)
                {
                    $r = $this->change_class_name($r);
                }
                $r = $this->remove_type_declare($r);
            }
            $this->proxy_code_content .= $r . "\r\n";
        }
        fclose($resource);
    }

    public function save_and_require(): void
    {
        $random_file_name = md5(uniqid().$this->short_name.time().rand(1000,9999)).'.php';
        $file_system = LocalFilesystemConstructor::getFilesystem();
        $file_path = "/storage/Framework/Runtime/{$random_file_name}";
        $file_system->write("/storage/Framework/Runtime/{$random_file_name}",$this->proxy_code_content);
        require BASE_DIR . $file_path;
    }

    public function getProxyClassName(): string
    {
        return $this->proxy_class_name;
    }

    private function remove_type_declare(string $line) :string
    {
        if(preg_match('/([a-z,A-Z]{4,})\s{1,}([a-z,A-Z,0-9]{1,})\s{1,}(\$[a-z,A-Z,0-9]{1,})\;/',$line))
        {
            $line = preg_replace('/([a-z,A-Z]{4,})\s{1,}([a-z,A-Z,0-9]{1,})\s{1,}(\$[a-z,A-Z,0-9]{1,})\;/','$1 $3;',$line);
        }
        return $line;
    }

    private function change_class_name(string $line) :string
    {
        $prefix = "PROXY_".substr(uniqid().time(),8,16);
        $proxy_class = "{$prefix}_{$this->short_name}";
        $this->proxy_class_name = $proxy_class;
        return str_replace($this->short_name,$proxy_class,$line);
    }

}
