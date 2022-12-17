<?php

namespace Watish\Components\Utils;

class WServ
{
    private $server;

    public function __construct($server)
    {
        $this->server = $server;
    }

    public function push(int $fd, string $data, int $opcode = WEBSOCKET_OPCODE_TEXT, bool $finish = true) :bool
    {
        return $this->server->push( $fd,  $data,  $opcode , $finish);
    }

    public function exists(int $fd) :bool
    {
        return $this->server->exists($fd);
    }

    public function disconnect(int $fd, int $code = SWOOLE_WEBSOCKET_CLOSE_NORMAL, string $reason = ''):bool
    {
        return $this->server->disconnect($fd,$code,$reason);
    }

    public function isEstablished(int $fd):bool
    {
        return $this->server->isEstablished($fd);
    }
}
