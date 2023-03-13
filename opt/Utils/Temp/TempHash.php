<?php

namespace Watish\Components\Utils\Temp;

use Illuminate\Filesystem\FilesystemAdapter;
use Opis\Closure\SerializableClosure;
use Swoole\Coroutine;
use Watish\Components\Constructor\LocalFilesystemConstructor;
use Watish\Components\Utils\Lock\GlobalLock;
use Watish\Components\Utils\Logger;

class TempHash
{
    private string $uuid;
    private string $fileName;
    private FilesystemAdapter $filesystem;

    public function __construct(string $uuid)
    {
        $this->uuid = substr(md5($uuid),8,16);
        $this->fileName = CACHE_PATH."/temp_hash_{$this->uuid}";
        $this->filesystem = LocalFilesystemConstructor::getRootIlluminateFilesystem();
    }

    public function hSet(string $key,string $hKey,mixed $value) :void
    {
        $fileName = $this->fileName."_{$key}";
        GlobalLock::lock($fileName);
        $data = $this->read($fileName);
        $data[$hKey] = $value;
        $this->put($fileName,$data);
        GlobalLock::unlock($fileName);
    }

    /**
     * @param string $key
     * @param string $hKey
     * @return mixed|null
     */
    public function hGet(string $key,string $hKey): mixed
    {
        $fileName = $this->fileName."_{$key}";
        $data = $this->read($fileName);
        return $data[$hKey] ?? null;
    }

    public function hExists(string $key,string $hKey) :bool
    {
        $fileName = $this->fileName."_{$key}";
        $data = $this->read($fileName);
        return isset($data[$hKey]);
    }

    public function hKeys(string $key): array
    {
        $fileName = $this->fileName."_{$key}";
        $data = $this->read($fileName);
        return array_keys($data);
    }

    public function hVals(string $key)
    {
        $fileName = $this->fileName."_{$key}";
        $data = $this->read($fileName);
        return array_values($data);
    }

    public function hDel(string $key,string $hKey): void
    {
        $fileName = $this->fileName."_{$key}";
        GlobalLock::lock($fileName);
        $data = $this->read($fileName);
        unset($data[$hKey]);
        $this->put($fileName,$data);
        GlobalLock::unlock($fileName);
    }

    public function get(string $key)
    {
        $fileName = $this->fileName."_{$key}";
        $data = $this->read($fileName);
        if(empty($data))
        {
            return null;
        }
        return $data;
    }

    public function set(string $key,mixed $value): void
    {
        $fileName = $this->fileName."_{$key}";
        GlobalLock::lock($fileName);
        $this->put($fileName,$value);
        GlobalLock::unlock($fileName);
    }

    public function del(string $key): bool
    {
        $fileName = $this->fileName."_{$key}";
        if($this->filesystem->fileExists($fileName))
        {
            try {
                $this->filesystem->delete($fileName);
            }catch (\Exception $exception)
            {
                Logger::exception($exception);
                return false;
            }
        }
        return true;
    }

    public function exists(string $key): bool
    {
        $fileName = $this->fileName."_{$key}";
        return $this->filesystem->fileExists($key);
    }

    private function put(string $filePath,mixed $data) :void
    {
        if($this->filesystem->fileExists($filePath))
        {
            $this->filesystem->delete($filePath);
        }
        if($data instanceof  \Closure)
        {
            $data = new SerializableClosure($data);
            $data = serialize($data);
        }else{
            $data = serialize($data);
        }
        try{
            $this->filesystem->write($filePath,$data);
        }catch (\Exception $exception)
        {
            Logger::exception($exception);
        }
    }

    private function read(string $filePath)
    {
        if(!$this->filesystem->fileExists($filePath))
        {
            return [];
        }
        $data = $this->filesystem->read($filePath);
        return unserialize($data);
    }

}