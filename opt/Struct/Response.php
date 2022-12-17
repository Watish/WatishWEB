<?php

namespace Watish\Components\Struct;

class   Response
{
    public \Swoole\Http\Response $response;
    public int $fd;

    public function __construct(\Swoole\Http\Response $response)
    {
        $this->response = $response;
        $this->fd = $response->fd;
    }

    public function header(string $key, string $value, bool $format = true):void
    {
        $this->response->header($key, $value, $format);
    }

    public function trailer(string $key, string $value, bool $ucwords = true):void
    {
        $this->response->trailer($key, $value, $ucwords);
    }

    public function cookie(string $key, string $value = '', int $expire = 0 , string $path = '/', string $domain  = '', bool $secure = false , bool $httponly = false, string $samesite = '', string $priority = ''):void
    {
        $this->response->cookie( $key,  $value ,  $expire , $path , $domain  , $secure  , $httponly ,  $samesite , $priority );
    }

    public function status(int $http_status_code = 200, string $reason = null) :bool
    {
        return $this->response->status($http_status_code,$reason);
    }

    public function gzip(int $level = 1) :void
    {
        $this->response->gzip($level);
    }

    public function redirect(string $url, int $http_code = 302) :void
    {
        $this->response->redirect($url,$http_code);
    }

    public function write(string $data): bool
    {
        return $this->response->write($data);
    }

    public function sendfile(string $filename, int $offset = 0, int $length = 0): bool
    {
        return $this->response->sendfile( $filename,  $offset , $length );
    }

    public function end(string $html = null): bool
    {
        if(!$html)
        {
            return $this->response->end();
        }
        return $this->response->end($html);
    }

    public function detach(): bool
    {
        return $this->response->detach();
    }

    public function create(int $fd): Response|false
    {
        $new_response =  \Swoole\Http\Response::create($fd);
        if(!$new_response)
        {
            return false;
        }
        return new Response($new_response);
    }

    public function isWritable():bool
    {
        return $this->response->isWritable();
    }

    /**
     * Websocket
     */

    public function upgrade():bool
    {
        return $this->response->upgrade();
    }

    public function recv(float $timeout = 0): Frame
    {
        return new Frame($this->response->recv($timeout));
    }

    public function push(string|object $data, int $opcode = WEBSOCKET_OPCODE_TEXT, bool $finish = true):bool
    {
        return $this->response->push($data,$opcode,$finish);
    }

    public function close():bool
    {
        return $this->response->close();
    }
}
