<?php

namespace Watish\Components\Includes;

class Process
{

    private array $processList;
    private array $processKeySet;

    public function __construct()
    {
        $this->processList = [];
        $this->processKeySet = [];
    }

    /**
     * @throws \Exception
     */
    public function Register(array $executed_class_list, string $process_name , int $worker_num = 1) :void
    {
        if(isset($this->processKeySet[$process_name]))
        {
            throw new \Exception("Duplicate Process Name");
        }
        $tmp_class = $executed_class_list[0];
        if(!isset($executed_class_list[1]))
        {
            $executed_class_list[1] = "execute";
        }
        $executed_class_list[0] = new $tmp_class();
        $executed_class_list = [$executed_class_list[0],"execute"];
        $this->processKeySet[$process_name] = $worker_num;
        $this->processList[] = [
            "callback" => $executed_class_list,
            "name" => $process_name,
            "pooled" => ($worker_num>1),
            "worker" => $worker_num
        ];
    }

    public function GetAllProcess():array
    {
        return $this->processList;
    }
}
