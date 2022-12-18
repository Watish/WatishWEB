<?php

use Swoole\Coroutine;
use Swoole\Coroutine\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Lock;
use Watish\Components\Constructor\AsyncTaskConstructor;
use Watish\Components\Constructor\ClassLoaderConstructor;
use Watish\Components\Constructor\CommandConstructor;
use Watish\Components\Constructor\LocalFilesystemConstructor;
use Watish\Components\Constructor\PdoPoolConstructor;
use Watish\Components\Constructor\ProcessConstructor;
use Watish\Components\Constructor\RedisPoolConstructor;
use Watish\Components\Constructor\RouteConstructor;
use Watish\Components\Constructor\WoopsConstructor;
use Watish\Components\Includes\Database;
use Watish\Components\Includes\Context;
use Watish\Components\Utils\Injector\ClassInjector;
use Watish\Components\Utils\Logger;
use Watish\Components\Utils\Table;

//Define project base dir
const BASE_DIR = __DIR__ . "/../";
define("CPU_SLEEP_TIME", (1 / swoole_cpu_num())*1000 );

//Composer
require_once BASE_DIR . '/vendor/autoload.php';

//Init Local file system
LocalFilesystemConstructor::init();

//Server Config
$server_config = require_once BASE_DIR .'/config/server.php';
define("SERVER_CONFIG", $server_config);

//DatabaseExtend Config
$database_config = require_once BASE_DIR . "/config/database.php";
define("DATABASE_CONFIG",$database_config);

//Init Table
Table::init(2048,32);
Table::set("server_config",$server_config);
Table::set("database_config",$database_config);

//Init Mysql Pool And QueryBuilder
PdoPoolConstructor::init();
$sqlConnection = PdoPoolConstructor::getSqlConnection();
$capsule = PdoPoolConstructor::getCapsule();
$pdoPool = PdoPoolConstructor::getPdoPool();

//Init RedisPool
$redisPool = RedisPoolConstructor::init();

//Init ClassLoader and Inject
ClassLoaderConstructor::init();

//Init Commando
CommandConstructor::init();
CommandConstructor::autoRegister();
CommandConstructor::handle();

//Process
ProcessConstructor::init();
$pidProcessSet = ProcessConstructor::getPidProcessSet();
$processList = ProcessConstructor::getProcessList();
$processNameSet = ProcessConstructor::getProcessNameSet();

//Init Context
$context = new Context();
$context->setProcesses($processNameSet);

//Init Route
RouteConstructor::init();
$route = RouteConstructor::getRoute();
$context->Set("Route",$route);

//Init Server Pool
$pool_worker_num = $server_config["worker_num"];
$pool = new Swoole\Process\Pool($pool_worker_num,1,0,true);
$pool->set(['enable_coroutine' => true]);
$context->setWorkerNum($pool_worker_num);

$pool->on('WorkerStart', function (\Swoole\Process\Pool $pool, $workerId) use ($processNameSet,$context,$route,$pool_worker_num,$server_config) {

    //Init Woops
    WoopsConstructor::init();

    //Init Injector and preCache all class loader
    ClassInjector::init();

    //Init DatabaseExtend in worker process
    PdoPoolConstructor::init();
    RedisPoolConstructor::init();

    //Init DatabaseExtend
    if(DATABASE_CONFIG["mysql"]["enable"])
    {
        Database::setPdoPool(PdoPoolConstructor::getPdoPool());
        Database::setSqlConnection(PdoPoolConstructor::getSqlConnection());
    }
    if(DATABASE_CONFIG["redis"]["enable"])
    {
        Database::setRedisPool(RedisPoolConstructor::getRedisPool());
    }

    //Init AsyncTask
    AsyncTaskConstructor::init($context->getProcess("Task"));

    //get worker process
    $worker_process = $pool->getProcess();

    //Init Worker Process,Pool
    $context->setWorkerPool($pool);
    $context->setWorkerId($workerId);

    //Init Context Lock
    $context->setLock(new Lock(SWOOLE_MUTEX));

    $server = new Server($server_config["listen_host"], $server_config["listen_port"], false , true);
    $server->set([
        'open_eof_check' => true,   //打开EOF检测
        'package_eof'    => "\r\n", //设置EOF
        'hook_flags'     => SWOOLE_HOOK_ALL
    ]);

    //Handle Request
    $server->handle('/',function (Request $request, Response $ws) use (&$context,$route,$server,$workerId){
        Logger::debug("Worker #{$workerId}");
        Logger::debug($request->server["request_uri"],"Request");
        $real_path = $request->server["request_uri"];
        $request_method = $request->getMethod();
        $context->Set("Request",$request);
        $context->Set("Response",$ws);
        if(!$route->path_exists($real_path))
        {
            //404
            $context->json([
                "Ok" => false,
                "Msg" => "Page Not Found"
            ],404);
            $context->abort();
            $context->reset();
            return;
        }
        $closure_array = $route->get_path_closure($real_path);
        $closure = $closure_array["callback"];
        $before_middlewares = $closure_array["before_middlewares"];
        $allow_methods = $closure_array["methods"];
        if($allow_methods and !in_array($request_method,$allow_methods))
        {
            $context->json([
                "Ok" => false,
                "Msg" => "Method Not Allowed"
            ],403);
            $context->abort();
            $context->reset();
            return;
        }
        $global_middlewares = $route->get_global_middlewares();
        $context->setServ($server);

        //Global Middleware
        if(count($global_middlewares) > 0)
        {
            foreach ($global_middlewares as $global_middleware)
            {
                Logger::debug("GlobalMiddleware...");
                try {
                    call_user_func_array([ClassInjector::getInjectedInstance($global_middleware),"handle"],[&$context]);
                }catch (Throwable $e)
                {
                    WoopsConstructor::handle($e,$context,"GlobalMiddleware");
                    return;
                }
            }
        }

        //Aborted After Global Middleware
        if($context->isAborted())
        {
            Logger::debug("Aborted!");
            $context->reset();
            return;
        }

        //Before Middleware
        if(count($before_middlewares) > 0)
        {
            foreach ($before_middlewares as $before_middleware)
            {
                Logger::debug("BeforeMiddleWare...");
                try {
                    call_user_func_array([ClassInjector::getInjectedInstance($before_middleware),"handle"],[&$context]);
                }catch (Throwable $e){
                    WoopsConstructor::handle($e,$context,"BeforeMiddleWare");
                    return;
                }
            }
        }

        //Aborted After Middleware
        if($context->isAborted())
        {
            Logger::debug("Aborted!");
            $context->reset();
            return;
        }

        //Controller
        Logger::debug("Controller...");
        try {
            $result = call_user_func_array([ClassInjector::getInjectedInstance($closure[0]),$closure[1]],[&$context]);
            if(isset($result))
            {
                if(is_string($result))
                {
                    $context->html($result);
                }elseif(is_array($result))
                {
                    $context->json($result);
                }else{
                    $context->html((string)$result);
                }
            }
        }catch (Throwable $e)
        {
            WoopsConstructor::handle($e,$context,"Controller");
            return;
        }

        //Aborted After Controller
        if($context->isAborted())
        {
            Logger::debug("Aborted!");
            $context->reset();
            return;
        }

        $context->reset();
    });
    //Watching Process By Single
    Coroutine::create(function() use ($pool,$pool_worker_num,$workerId,&$context,&$processNameSet){
        if($workerId !== $pool_worker_num-1)
        {
            return;
        }
        while(1)
        {
            $socketNameSet = [];
            foreach ($processNameSet as $name => $processList)
            {
                $index = 0;
                foreach ($processList as $process)
                {
                    $index++;
                    if(!isset($socketNameSet["{$name}-{$index}"]) or !$socketNameSet["{$name}-{$index}"])
                    {
                        $socket = $process->exportSocket();
                        $socketNameSet["{$name}-{$index}"] = $socket;
                    }else{
                        $socket = $socketNameSet["{$name}-{$index}"];
                    }

                    $receive = $socket->recv();
                    if($receive)
                    {
                        Logger::debug("From Process $name Receive: $receive");
                    }
                }
            }
            usleep(CPU_SLEEP_TIME);
        }
    });

    //Watching Worker Process
    Coroutine::create(function() use (&$context,&$worker_process) {
        $cid = Coroutine::getuid();
        $worker_id = $context->getWorkerId();
        Logger::debug("Worker #{$worker_id} Cid #{$cid} Started");
        $socket = $worker_process->exportSocket();
        while (1)
        {
            $socket = $worker_process->exportSocket();
            $rec = $socket->recv();
            if($rec)
            {
                try{
                    $handler = new \Watish\Components\Utils\Worker\SignalHandler($rec);
                    $handler->handle($context);
                }catch (Exception $e)
                {
                    Logger::error($e->getMessage());
                }
            }
            Swoole\Coroutine::sleep(CPU_SLEEP_TIME);
        }
    });

    $context->setServ($server);
    $server->start();
});
Logger::clear();
Logger::CLImate()->bold()->white()->addArt(BASE_DIR."/storage/Framework")->draw("Logo");
$pool->start();
