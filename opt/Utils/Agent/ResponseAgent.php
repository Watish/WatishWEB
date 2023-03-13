<?php

namespace Watish\Components\Utils\Agent;

use Opis\Closure\SerializableClosure;
use Watish\Components\Struct\Response;
use Watish\Components\Utils\Lock\GlobalLock;
use Watish\Components\Utils\Logger;

/**
 * @Description 为ws协议response提供统一代理
 */
class ResponseAgent
{
    private \Swoole\Http\Response $response;
    private int $fd;

    private array $closureHash = [];
    private string $uuid;

    public function __construct(Response $response,string $uuid)
    {
        $this->response = $response->response;
        $fd = $response->fd;
        $this->uuid = $uuid;
        if(!$fd)
        {
            $this->fd = -1;
        }else{
            $this->fd = $fd;
        }
    }

    public function onOpen(\Closure $closure) :void
    {
        $this->closureHash["onOpen"] = serialize(new SerializableClosure($closure));
    }

    public function onError(\Closure $closure) :void
    {
        $this->closureHash["onError"] = serialize(new SerializableClosure($closure));
    }

    public function onClose(\Closure $closure) :void
    {
        $this->closureHash["onClose"] = serialize(new SerializableClosure($closure));
    }

    public function onMessage(\Closure $closure) :void
    {
        $this->closureHash["onMessage"] = serialize(new SerializableClosure($closure));
    }

    public function useResponse(\Closure $closure): void
    {
        GlobalLock::lock($this->uuid);
        $response = \Swoole\Http\Response::create($this->fd);
        if($response instanceof  \Swoole\Http\Response)
        {
            $closure($response);
        }else{
            Logger::error(json_encode($response),"Response");
        }

        GlobalLock::unlock($this->uuid);
    }

    public function confirm() :void
    {
        Logger::info($this->fd);
        $this->response->detach();
        $data = json_encode([
            "fd" => $this->fd,
            "closureHash" => $this->closureHash
        ]);
        Logger::info($data);
    }

    public function isValid() :bool
    {
        return $this->fd;
    }
}