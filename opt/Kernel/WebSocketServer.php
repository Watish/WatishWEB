<?php

namespace Watish\Components\Kernel;

use Watish\Components\Includes\Context;
use Watish\Components\Includes\Process;
use Watish\Components\Includes\Route;
use Watish\Components\Utils\ProcessSignal;
use function Swoole\Coroutine\run;

class WebSocketServer
{
    public \Swoole\WebSocket\Server $serv;
    public Context $context;
    private Route $route;
    private Process $process;
    private array $processList;
    private array $pidProcessSet;
    private array $processNameSet;

    public function __construct()
    {
        //Init Context
        $this->context = new Context();

        //Init Server
        $this->serv = new \Swoole\WebSocket\Server('0.0.0.0', 9502);
        //设置异步任务的工作进程数量
        $this->serv->set([
            'task_worker_num' => swoole_cpu_num(),
            'worker_num' => swoole_cpu_num(),
            "enable_coroutine" => true
        ]);

        //Routes
        $this->route = new Route();
        $this->register_routes();

        //Process
        $this->process = new Process();
        $this->register_process();
        $this->processNameSet = [];
        $this->processList = [];
        $this->start_process();

        //Context Set
        $this->context->setServ($this->serv);
        $this->context->Set("Route",$this->route);
        $this->context->Set("Process",$this->processNameSet);

        //On Start
        $this->serv->on('Start',[$this,"onStart"]);

        //监听WebSocket消息事件
        $this->serv->on('Message', [$this, "onMessage"]);

        //监听WebSocket连接关闭事件
        $this->serv->on('Close', [$this, "onClose"]);

        //Task
        $this->serv->on('Task', [$this, "onTask"]);

        //Task Finish
        $this->serv->on('Finish', [$this, "onFinish"]);


    }

    public function Start(): void
    {
        //Set Context
        $this->context->setServ($this->serv);
        $this->serv->start();
    }

    public function onStart($serv) :void
    {
        echo "#### onStart ####".PHP_EOL;
        echo "SWOOLE 服务已启动".PHP_EOL;
        echo "master_pid: {$serv->master_pid}".PHP_EOL;
        echo "manager_pid: {$serv->manager_pid}".PHP_EOL;
        echo "########".PHP_EOL.PHP_EOL;
    }

    public function register_routes(): void
    {
        require BASE_DIR . '/config/route.php';
        do_register_routes($this->route);
    }

    public function register_process(): void
    {
        require BASE_DIR . '/config/process.php';
        do_register_process($this->process);
    }

    public function start_process() :void
    {
        echo "Start Process \r\n";
        $context = $this->context;
        $executed_list = $this->process->GetAllProcess();
        foreach ($executed_list as $process_array)
        {
            $process_name = $process_array["name"];
            $list_executed_array = $process_array["callback"];
            $process = new \Swoole\Process(function (\Swoole\Process $proc) use ($list_executed_array,$context){
                echo "Process Registered :".time()."\r\n";
                call_user_func_array($list_executed_array,[$context,$proc]);
            },false,SOCK_DGRAM, true);
            $status = $process->start();
            $pid = $process->pid;
            if($status)
            {
                echo "Process PID-{$pid} Start Successfully \r\n";
                $this->pidProcessSet[$pid] = $process;
                $this->processNameSet[$process_name] = $process;
                $this->processList[] = $process;
            }else{
                echo "Process PID-{$pid} Error \r\n";
            }
        }
    }

    public function process_watching():void
    {
        $processList = $this->processList;
        run(function() use ($processList){
            while (1)
            {
                foreach ($processList as $process)
                {
                    $socket = $process->exportSocket();
                    $res = $socket->recv();
                    $pid = $process->pid;
                    if(!$res)
                    {
                        continue;
                    }else{
                        $signal = ProcessSignal::Parse($res);
                        $type = $signal["TYPE"];
                        echo "Signal Received From Process PID-{$pid} , Type [{$type}] \r\n";
                    }
                }
            }
        });
    }

    public function onMessage($ws, $frame): void
    {
        $this->context->Set("fd", $frame->fd);
        $this->context->Set("data", $frame->data);
        $this->context->Set("frame", $frame);
        $this->route->handle("test", [$this->context]);
    }

    public function onClose($ws, $fd): void
    {
        echo "client-{$fd} is closed\n";
    }

    public function onTask($serv, $task_id, $reactor_id, array $data): void
    {
        $params = $data["params"];
        call_user_func($data["executed"],...$params);
        $this->serv->finish("$reactor_id");
    }

    public function onFinish($serv, $task_id, $data): void
    {
        echo "AsyncTask[{$task_id}] Finish: {$data}" . PHP_EOL;
    }

}
