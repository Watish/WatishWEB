<?php

namespace Watish\Components\Struct;

class Frame
{
    public mixed $frame;
    public int|null $fd;
    public mixed $data;
    public mixed $opcode;
    public bool $finish;
    private bool $closed;

    public function __construct($frame)
    {
        $this->closed = false;
        if(!isset($frame->fd))
        {
            $this->closed = true;
            return;
        }
        if ($frame == '') {
            $this->closed = true;
            return;
        }
        if (!$frame) {
            $this->closed = true;
            return;
        }
        if(isset($frame->code) and $frame->code == 1000)
        {
            $this->closed = true;
            return;
        }
        if($frame->data == "")
        {
            $this->closed = true;
            return;
        }
        $this->frame = $frame;
        $this->fd = $frame->fd;
        $this->data = $frame->data;
        $this->opcode = $frame->opcode;
        $this->finish = $frame->finish;
    }

    public function isClosed():bool
    {
        return $this->closed;
    }
}
