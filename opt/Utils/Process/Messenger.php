<?php

namespace Watish\Components\Utils\Process;

use Swoole\Coroutine;
use Watish\Components\Utils\Temp\TempList;

class Messenger
{
    private string $uuid;
    private TempList $tempList;

    public function __construct(string $uuid)
    {
        $this->uuid = $uuid;
        $this->tempList = new TempList($this->uuid);
    }

    /**
     * @return mixed|null
     */
    public function recv() :mixed
    {
        if($this->tempList->isEmpty())
        {
            return null;
        }
        return $this->tempList->pop();
    }

    public function send(string $name,mixed $data) :void
    {
        $messager = ProcessManager::get_messager_by_name($name);
        if(is_null($messager))
        {
            return;
        }
        if(Coroutine::getCid() > 0)
        {
            Coroutine::create(function () use ($messager,$data){
                $messager->write($data);
            });
            return;
        }
        $messager->write($data);
    }

    public function write(mixed $data) :void
    {
        if(Coroutine::getCid() > 0)
        {
            Coroutine::create(function() use ($data){
                $this->tempList->push($data);
            });
            return;
        }
        $this->tempList->push($data);
    }
}