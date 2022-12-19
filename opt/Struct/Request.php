<?php

namespace Watish\Components\Struct;

/**
 * @Description Gracefully Using Swoole\Http\Request
 */
class Request
{
    public \Swoole\Http\Request $request;
    public array $header;
    public array $server;
    public array|null $get;
    public array|null $post;
    public array|null $cookie;
    public array|null $files;

    public function __construct(\Swoole\Http\Request $request)
    {
        $this->request = $request;
        $this->header = $request->header;
        $this->server = $request->server;
        $this->get = $request->get;
        $this->post = $request->post;
        $this->cookie = $request->cookie;
        $this->files = $request->files;
    }

    public function getContent():string
    {
        return $this->request->getContent();
    }

    public function GetAll():array
    {
        if(!$this->get)
        {
            return [];
        }
        return $this->get;
    }

    public function PostAll() :array
    {
        if(!$this->post)
        {
            return [];
        }
        return $this->post;
    }

    public function all() :array
    {
        $res = [];
        if($this->get)
        {
            foreach ($this->get as $param => $value)
            {
                $res[$param] = $value;
            }
        }
        if($this->post)
        {
            foreach ($this->post as $param => $value)
            {
                $res[$param] = $value;
            }
        }
        return $res;
    }

    public function getData():string
    {
        return $this->request->getData();
    }

    public function create(array $options): Swoole\Http\Request|false
    {
        return $this->request->create($options);
    }

    public function getMethod()
    {
        return $this->request->getMethod();
    }

    public function existsParam($key):bool
    {
        if(isset($this->get[$key]))
        {
            return true;
        }
        if(isset($this->post[$key]))
        {
            return true;
        }
        return false;
    }



}
