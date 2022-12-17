<?php

namespace Watish\Components\Utils\Worker;


use Watish\Components\Includes\Context;

class SignalHandler
{
    private string $status;
    /**
     * @var string|null
     */
    private mixed $uuid;

    /**
     * @var string|null
     */
    private mixed $msg;

    /**
     * @var mixed|null
     */
    private string $key;

    /**
     * @var mixed|null
     */
    private mixed $data;

    public function __construct(string $rec)
    {
        $data = json_decode($rec,true);
        $this->status = $data["TYPE"];
        isset($data["UUID"]) ? $this->uuid = $data["UUID"] : $this->uuid = null;
        isset($data["MSG"]) ? $this->msg = $data["MSG"] : $this->msg = null;
        isset($data["KEY"]) ? $this->key = $data["KEY"] : $this->key = null;
        isset($data["DATA"]) ? $this->data = $data["DATA"] : $this->data = null;
    }

    public function handle(Context $context) :void
    {
        $worker_id = $context->getWorkerId();
//        Logger::debug("#{$worker_id} ");
//        Logger::debug($context->GetGlobalSet());
        $status = $this->status;
        $this->$status($context);
    }

    private function KV_SET(Context $context): void
    {
        $context->global_Set($this->key,$this->data);
    }

    private function KV_DEL(Context $context):void
    {
        $context->global_Del($this->key);
    }

    private function SET_ADD(Context $context):void
    {
        $context->globalSet_Add($this->key,$this->data,$this->uuid,false);
    }

    private function SET_DEL(Context $context):void
    {
        $context->globalSet_Del($this->key,$this->uuid,false);
    }

    private function SET_PUSH_ALL(Context $context):void
    {
        if($context->global_Exists($this->key))
        {
            $items = $context->globalSet_items($this->key);
            if($items)
            {
                foreach ($items as  $item)
                {
                    $item->push($this->msg);
                }
            }
        }
    }

    private function SET_PUSH(Context $context):void
    {
        if($context->globalSet_Exists($this->key,$this->uuid))
        {
            $response = $context->globalSet_Get($this->key,$this->uuid);
            $response->push($this->msg);
        }
    }

    private function KV_PUSH(Context $context):void
    {
        if($context->global_Exists($this->key))
        {
            $response = $context->global_Get($this->key);
            $response->push($this->msg);
        }
    }


}
