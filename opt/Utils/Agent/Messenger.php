<?php

namespace Watish\Components\Utils\Agent;

use Swoole\Http\Response;
use Watish\Components\Utils\Temp\TempHash;

class Messenger
{
    private int $fd;
    private TempHash $tempHash;

    public function __construct(int $fd)
    {
        $this->fd = $fd;
        $this->tempHash = new TempHash("ResponseMessenger");
    }

    public function sendTo(string $uuid,string $data): bool
    {
        if(!$this->tempHash->hExists("fd",$uuid))
        {
            return false;
        }
        $fd = $this->tempHash->hGet("fd",$uuid);
        /**
         * @var Response $response
         */
        $response = Response::create($fd);
        $response->push($data);
        $response->detach();
        return true;
    }

    public function sendAll(string $data): bool
    {
        $fds = $this->tempHash->hVals("fd");
        foreach ($fds as $fd)
        {
            $response = Response::create($fd);
            $response->push($data);
            $response->detach();
        }
        return true;
    }
}