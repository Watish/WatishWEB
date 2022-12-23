<?php

namespace Watish\Components\Struct;

use Symfony\Component\HttpFoundation\File\UploadedFile;

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
    private array $route_params;

    public function __construct(\Swoole\Http\Request $request,array $route_params = [])
    {
        $this->request = $request;
        $this->header = $request->header;
        $this->server = $request->server;
        $this->get = $request->get;
        $this->post = $request->post;
        $this->cookie = $request->cookie;
        $this->files = $request->files;
        $this->route_params = $route_params;
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
        if($this->files)
        {
            foreach ($this->files as $param => $file)
            {
                $res[$param] = new UploadedFile($file["tmp_name"], $file["name"], $file["type"]);
            }
        }
        return $res;
    }

    public function route($name)
    {
        if(!isset($this->route_params[$name]))
        {
            return null;
        }
        return $this->route_params[$name];
    }

    public function file($param) :null|UploadedFile
    {
        if(!$this->files)
        {
            return null;
        }
        if(!isset($this->files[$param]))
        {
            return null;
        }
        $file = $this->files[$param];
        return new UploadedFile($file["tmp_name"], $file["name"], $file["type"]);
    }

    /**
     * @return UploadedFile[]
     */
    public function files() :array
    {
        if(!$this->files)
        {
            return [];
        }
        $res = [];
        foreach ($this->files as $name => $file)
        {
            $res[$name] = new UploadedFile($file["tmp_name"], $file["name"], $file["type"]);
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
