<?php

namespace Watish\Components\Utils\Temp;

use Illuminate\Filesystem\FilesystemAdapter;
use Opis\Closure\SerializableClosure;
use Swoole\Coroutine;
use Watish\Components\Constructor\LocalFilesystemConstructor;
use Watish\Components\Utils\Lock\GlobalLock;
use Watish\Components\Utils\Logger;

class TempList
{
    private string $uuid;
    private FilesystemAdapter $filesystem;
    private string $filePath;

    public function __construct(string $uuid)
    {
        $uuid = md5($uuid);
        $this->uuid = $uuid;
        $this->filePath = CACHE_PATH."/list_{$uuid}";
        $this->filesystem = LocalFilesystemConstructor::getRootIlluminateFilesystem();
        if(!$this->filesystem->fileExists($this->filePath))
        {
            if(Coroutine::getCid() > 0) {
                GlobalLock::lock("list_{$this->uuid}");
            }
            $this->put([]);
            if(Coroutine::getCid() > 0) {
                GlobalLock::unlock("list_{$this->uuid}");
            }
        }
    }

    public function push(mixed $data): void
    {
        if(Coroutine::getCid() > 0)
        {
            Coroutine::create(function () use ($data){
                GlobalLock::lock("list_{$this->uuid}");
                $list = $this->read();
                array_unshift($list,$this->serialize($data));
                $this->put($list);
                GlobalLock::unlock("list_{$this->uuid}");
            });
            return;
        }
        $list = $this->read();
        $list[] = $this->serialize($data);
        $this->put($list);
    }

    /**
     * @return mixed|null
     */
    public function pop() :mixed
    {
        if(Coroutine::getCid() > 0) {
            GlobalLock::lock("list_{$this->uuid}");
        }
        $list = $this->read();
        if(empty($list))
        {
            $item = null;
        }else{
            $item = unserialize(array_pop($list));
            $this->put($list);
        }
        if(Coroutine::getCid() > 0) {
            GlobalLock::unlock("list_{$this->uuid}");
        }
        return $item;
    }

    public function isEmpty(): bool
    {
        if(!$this->filesystem->fileExists($this->filePath))
        {
            return true;
        }
        return empty($this->read());
    }

    private function read() :mixed
    {
        return unserialize($this->filesystem->read($this->filePath));
    }

    private function put(array $data) :void
    {
        if($this->filesystem->fileExists($this->filePath))
        {
            $this->filesystem->delete($this->filePath);
        }
        try{
            $this->filesystem->write($this->filePath,$this->serialize($data));
        }catch (\Exception $exception)
        {
            Logger::exception($exception);
        }
    }

    private function serialize(mixed $data) :string
    {
        if($data instanceof \Closure)
        {
            $data =  new SerializableClosure($data);
            $data = $data->serialize();
        }else{
            $data = serialize($data);
        }
        return $data;
    }


}