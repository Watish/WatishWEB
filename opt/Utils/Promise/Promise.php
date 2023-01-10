<?php


namespace Watish\Components\Utils\Promise;

use Closure;
use Swoole\Coroutine;
use Watish\Components\Struct\Channel\UnlimitedChannel;

class Promise
{
    private \Exception $catchException;
    private bool $error = false;
    private bool $first = true;
    private bool $hasThen = false;
    private bool $hasCatch = false;
    private Closure $closure;
    private string $uuid = "";
    private array $closureList = [];

    public function __construct(Closure $closure)
    {
        $this->closure = $closure;
        return $this;
    }

    public function setName(string $name) :Promise
    {
        $this->uuid = $name;
        return $this;
    }

    public function then($thenClosure = null, $rejectClosure = null): Promise
    {
        if($this->first)
        {
            $this->first = false;
            PromiseWorker::init($this->uuid());
            $this->closureList[] = [
                "type" => "resolve",
                "closure" => $this->closure
            ];
            PromiseWorker::pushResolve($this->uuid(),$this->closure);
        }
        if (!is_null($thenClosure)) {
            //Set ThenClosure Only
            PromiseWorker::init($this->uuid());
            $this->closureList[] = [
                "type" => "resolve",
                "closure" => $thenClosure
            ];
            PromiseWorker::pushResolve($this->uuid(),$thenClosure);
        }
        if (!is_null($rejectClosure)) {
            PromiseWorker::init($this->uuid());
            $this->closureList[] = [
                "type" => "reject",
                "closure" => $rejectClosure
            ];
            PromiseWorker::pushReject($this->uuid(),$rejectClosure);
        }
        return $this;
    }

    public function catch($rejectClosure): static
    {
        PromiseWorker::init($this->uuid());
        $this->closureList[] = [
            "type" => "reject",
            "closure" => $rejectClosure
        ];
        PromiseWorker::pushReject($this->uuid(),$rejectClosure);
        return $this;
    }

    private function uuid():string
    {
        if($this->uuid == "")
        {
            $this->uuid = md5(uniqid().time().rand(1,9999));
        }
        return $this->uuid;
    }

    /**
     * @return Closure
     */
    public function getClosure(): Closure
    {
        return $this->closure;
    }

}
