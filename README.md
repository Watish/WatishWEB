# Watish WEB

### 一个swoole驱动的多进程全协程的轻量Web框架

#### 技术栈

Swoole，PHP

#### 框架特点

+ 支持websocket
+ 通过UnixSocket实现多进程间的全局变量一致
+ 支持独立进程Process
+ 支持Crontab定时任务
+ 基于协程且生产可用的优雅异步回调Promise
+ 支持Task异步投递闭包任务
+ 支持路由注解，中间件注解，全局中间件注解，CLI命令注解
+ 支持AOP面向切片开发
+ 对Swoole\Table进行了封装，使用分块存储，现可以用键值对存放任何数据（包括闭包）

#### 环境要求

+ PHP 8+
+ Swoole v5.0+

### 快速开始

#### 使用Git

```shell
git clone https://github.com/Watish/WatishWEB
```

#### 使用Composer

```shell
composer create-project watish/watishweb:dev-master
```



### 启动项目

**项目的入口文件为  项目/bin/CoServer.php**

#### 使用[swoole-cli](https://github.com/swoole/swoole-cli) （推荐）

```shell
swoole-cli ./bin/CoServer.php
```

#### 使用php（需安装swoole拓展）

```
php ./bin/CoServer.php
```



### 目录结构

- bin/ 入口文件
- config/ 配置文件目录
- src/ 业务逻辑目录
- opt/ 项目工具类目录
- storage/ 存储目录
    - Framework/ 用于存放项目生成文件，如代理类缓存
    - View/ 用于存放视图文件（挖坑）
- vendor/ 组件目录
- tools/
    - php-cs-fixer/
- vendor-bin/
    - box/



### 编写一个Hello World

在 **src/Controller**目录下新建一个类，这里我们定义为**HelloController**

```php
<?php

namespace Watish\WatishWEB\Controller;

use Watish\Components\Attribute\Path;
use Watish\Components\Struct\Request;

class HelloController
{
    #[Path('/')]
    public function index(Request $request) :array
    {
        return [
            "msg" => "hello world"
        ];
    }
}
```

保存后，启动项目，访问 http://127.0.0.1:9502/ 便能看到

```json
{"msg":"hello world"}
```

是不是很简单 😜



### 注解 Attribute

- Inject 依赖注入，属性注解 Inject(string $class)
- Middleware 局部中间件，方法注解，类注解 Middleware(array $middlewares)
- GlobalMidlleware 全局中间件，类注解 GlobalMidlleware 无参数
- Asyc 异步执行，方法注解 Async 无参数
- Aspect 切片，方法注解 Aspect(string $class)
- Command 命令，类注解 Command(string $command , string $prefix)
- Crontab 定时任务，类注解 Crontab(string $rule)





### 上下文管理 Context

不同于传统的php-fpm形式，**多进程之间存在内存隔离**，这意味着在进程A设定的变量进程B是无法获取的，此外，**请求与请求之间并不是隔离的**，也就是说，在同一进程下的两个请求，尽管在不同的协程中处理逻辑，如果都对全局变量A修改，那么全局变量会被修改两次

具体可查阅swoole文档中的 [**编程须知**#严重错误](https://wiki.swoole.com/#/coroutine/notice?id=%e4%b8%a5%e9%87%8d%e9%94%99%e8%af%af)

使用 **Watish\Components\Includes\Context** 可以有效规避上述问题

**Context**是一个静态类，不仅提供了简单的**Get**，**Set**方法，还通过进程通信提供了**多worker进程**全局变量的GlobalSet，GlobalGet等方法

注：多worker进程全局变量仅适用于广播通信的业务场景，请勿重度依赖GlobalSet等基于多进程通信统一的方法，如需高并发，数据强一致请使用 **Watish\Components\Utils\Table** ，一个对 **Swoole\Table** 的封装，可以充分利用每一行资源，并支持闭包序列化



### 请求 Request

当浏览器发送请求至服务器，服务器会调用handle方法，随后通过路由调度器判断请求路由是否存在，存在解析路由参数，封装至 **Watish\Components\Struct\Request**，传入 **全局中间件 -> 局部中间件 -> 控制器**



### 路由 Route

注册路由的两种方式

##### 通过Prefix,Path注解注册

注：需要在 **/config/server.php **中修改 **register_route_auto **为 **true**

```php
...
"register_route_auto" => true
...
```

**Prefix**是**类注解**，定义该类下路由的前缀

```php
#[Prefix(string $prefix)]
```

**Path**是**方法注解**，定义路由路径

```php
#[Prefix(string $path,array $methods)]
```

举个栗子：

```php
<?php

namespace Watish\WatishWEB\Controller;

use Watish\Components\Attribute\Middleware;
use Watish\Components\Attribute\Path;
use Watish\Components\Attribute\Prefix;
use Watish\Components\Struct\Request;
use Watish\WatishWEB\Middleware\TestMiddleware;

#[Prefix('/hello')]
class HelloController
{
    #[Path('/index')]
    public function index(Request $request) :array
    {
        return [
            "msg" => "hello world"
        ];
    }

    #[Path('/user/{name}',['GET','POST'])]
    #[Middleware([TestMiddleware::class])]
    public function msg(Request $request) :array
    {
        return [
            "msg" => "hello ".$request->route('name')
        ];
    }
}
```

上述代码的路由如下

| 路径                 | 控制器                   | 方法       | 中间件            |
|--------------------|-----------------------|----------|----------------|
| /hello/index       | HelloController@index | ANY      | 无              |
| /hello/user/{name} | HelloController@msg   | GET,POST | TestMiddleware |

##### 通过配置文件注册路由

路由配置文件路径为：项目/config/route.php

复用上面的栗子，则上述路由配置应如下

```php
<?php


use Watish\Components\Includes\Route;
use Watish\WatishWEB\Controller\HelloController;

function do_register_global_middleware(Route $route):void
{
    /**
    $route->register_global_middleware(CorsMiddleware::class);
     */
}

function do_register_routes(Route $route): void
{
    $route->register('/hello/index',[HelloController::class,'index'],[],[]);
    $route->register('/hello/user/{name}',[HelloController::class,'msg'],[TestMiddleware:class],['GET','POST']);
}
```

register方法传参如下

```php
Watish\Components\Includes\Route->register(string $path , array $callback , array $before_middlewares , array $methods )
```



### 中间件 Middleware

注：中间件都要implement MiddlewareInterface接口

#### 全局中间件

**通过注解注册**

可以通过使用 **GlobalMiddleware** 的 **类注解** 实现全局中间件的注册

举个例子：

```php
<?php

namespace Watish\WatishWEB\Middleware;

use Watish\Components\Attribute\GlobalMiddleware;
use Watish\Components\Struct\Request;
use Watish\Components\Struct\Response;

#[GlobalMiddleware]
class CorsMiddleware implements MiddlewareInterface
{
    public function handle(Request $request,Response $response): void
    {
        $response->header("Access-Control-Allow-Origin", "*");
        $response->header("Access-Control-Allow-Credentials", true);
    }
}
```

**通过路由注册**

配置文件路径为：项目/config/route.php

```php
<?php

use Watish\Components\Includes\Route;
use Watish\WatishWEB\Controller\HelloController;
use Watish\WatishWEB\Middleware\CorsMiddleware;

function do_register_global_middleware(Route $route):void
{
    $route->register_global_middleware(CorsMiddleware::class);
}

function do_register_routes(Route $route): void
{
    $route->register('/hello/index',[HelloController::class,'index'],[],[]);
    $route->register('/hello/user/{name}',[HelloController::class,'msg'],[],['GET','POST']);
}
```



#### 局部中间件

**通过注解注册**

可以使用 **Middleware** 来对**控制器**或者某个**方法**进行注解

```php
#[Middleware(array $middlewares)]
```

先创建一个 **TestMiddleware**

```php
<?php

namespace Watish\WatishWEB\Middleware;

use Watish\Components\Struct\Request;
use Watish\Components\Struct\Response;

class TestMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Response $response)
    {
        $response->header("test","test");
    }
}
```

然后修改 **HelloController**

```php
<?php

namespace Watish\WatishWEB\Controller;

use Watish\Components\Attribute\Middleware;
use Watish\Components\Attribute\Path;
use Watish\Components\Attribute\Prefix;
use Watish\Components\Struct\Request;
use Watish\WatishWEB\Middleware\TestMiddleware;

#[Prefix('/hello')]
class HelloController
{
    #[Path('/index')]
    #[Middleware([TestMiddleware::class])]
    public function index(Request $request) :array
    {
        return [
            "msg" => "hello world"
        ];
    }

    #[Path('/user/{name}',['GET','POST'])]
    #[Middleware([TestMiddleware::class])]
    public function msg(Request $request) :array
    {
        return [
            "msg" => "hello ".$request->route('name')
        ];
    }
}
```

如上，index方法和msg方法都有了局部中间件 **TestMiddleware**

当然，上述代码还能一下这样写，直接给HelloController添加 **Middleware** 注解

```php
<?php

namespace Watish\WatishWEB\Controller;

use Watish\Components\Attribute\Middleware;
use Watish\Components\Attribute\Path;
use Watish\Components\Attribute\Prefix;
use Watish\Components\Struct\Request;
use Watish\WatishWEB\Middleware\TestMiddleware;

#[Prefix('/hello')]
#[Middleware([TestMiddleware::class])]
class HelloController
{
    #[Path('/index')]
    public function index(Request $request) :array
    {
        return [
            "msg" => "hello world"
        ];
    }

    #[Path('/user/{name}',['GET','POST'])]
    public function msg(Request $request) :array
    {
        return [
            "msg" => "hello ".$request->route('name')
        ];
    }
}
```

**通过配置文件注册**

参考路由章节中的配置文件路由注册方法 register 传参 ，此处不做赘述



### 控制器 Controller

控制器是整个业务项目的核心，负责处理请求，调用服务，返回数据

比较简单，不多描述

配合**依赖注入**，举个栗子：

```php
<?php

namespace Watish\WatishWEB\Controller;

use Watish\Components\Attribute\Inject;
use Watish\Components\Attribute\Middleware;
use Watish\Components\Attribute\Path;
use Watish\Components\Attribute\Prefix;
use Watish\Components\Struct\Request;
use Watish\WatishWEB\Middleware\TestMiddleware;
use Watish\WatishWEB\Service\BaseService;

#[Prefix('/hello')]
#[Middleware([TestMiddleware::class])]
class HelloController
{
    #[Inject(BaseService::class)]
    public BaseService $baseService;

    #[Path('/index')]
    public function index(Request $request) :array
    {
        return [
            "msg" => $this->baseService->toArray(["Hello",'World'])
        ];
    }

    #[Path('/user/{name}',['GET','POST'])]
    public function msg(Request $request) :array
    {
        return [
            "msg" => "hello ".$request->route('name')
        ];
    }
}
```

注：暂不支持构造方法注入，后续会完善（挖坑）



### 服务 Service

直接贴代码

```php
<?php

namespace Watish\WatishWEB\Service;

use Watish\Components\Attribute\Async;
use Watish\Components\Attribute\Inject;
use Watish\Components\Utils\Logger;

class TestService
{
    #[Inject(BaseService::class)]
    public BaseService $baseService;
    
    #[Async]
    public function asyncHello(): void
    {
        Logger::info("Hello");
    }
    
    public function hello(string $name) :string
    {
        return "hello {$name}";
    }
}
```

在Service中，仍然可以进行依赖注入，此外，还可以对方法进行Async注解（注意，被Async注解的方法必须是void类型）使其成为一个异步方法



### 命令 Command

Command类文件存放于 **项目/src/Command/**

注：Command类需要implement CommandInterface 接口

命令类只能使用注解注册命令

示例代码如下：

```php
<?php

namespace Watish\WatishWEB\Command;

use Watish\Components\Attribute\Command;
use Watish\Components\Utils\Logger;

#[Command("hello","command")]
class HelloCommand implements CommandInterface
{
    public function handle(): void
    {
        Logger::info("Hello");
    }

}
```

上述代码，可以通过以下方式执行

**swoole-cli**

```shell
swoole-cli ./bin/CoServer.php command:hello
```

**PHP**

```
php ./bin/CoServer.php command:hello
```

注解Command的用法

```php
Command(string $command , string $prefix = "command")
```



### Task 定时任务

Task类存放于 **项目/src/Task/**

注：所有的Task类都要implement TaskInterface

Task类只支持使用**Crontab注解**注册定时任务

示例代码如下：

```php
<?php

namespace Watish\WatishWEB\Task;

use Watish\Components\Attribute\Crontab;
use Watish\Components\Utils\Logger;

#[Crontab("* * * * *")]
class HelloTask implements TaskInterface
{
    public function execute(): void
    {
        Logger::info("Hello","HelloTask");
    }
}
```

这是一个每秒都会输出Hello的定时任务

Crontab注解使用方法

```php
Crontab(string $rule)
```

其中，rule为标准的**crontab表达式**



### 数据库 Database

注：暂只有mysql，redis（可自己加）

本框架使用了连接池来维护mysql，redis连接，并于启动初完成了连接池的创建，现只需在业务逻辑中使用即可

**Watish\Components\Includes\Database::mysql() ** 返回一个对Laravel查询构造器的封装（主要是改变了底层Pdo逻辑，正常使用无差异）

**Watish\Components\Includes\Database::redis()** 返回一个Predis的Client

**请先配置数据库！** 配置文件：**项目/config/database.php**



### 其它工具

框架使用了以下组件，并对某些组件进行了封装

在 **Watish\Components\Constructor** 命名空间下，提供了对一些组件的快速构造

#### 异步任务

**AsyncTaskConstructor::make()** 异步任务投递

#### 文件系统

**LocalFilesystemConstructor::getFilesystem()** 本地文件系统构造器

#### 表单验证

**ValidatorConstructor::make(array $data , array $rules)** Validator构造器



### 关于框架

#### 框架用到的组件

感谢优秀的组件开发者

- ext-mbstring
- predis/predis
- illuminate/database
- ext-sockets
- opis/closure
- league/flysystem
- ext-pdo
- dragonmantank/cron-expression
- league/climate
- filp/whoops
- ulrichsg/getopt-php
- illuminate/validation
- nikic/fast-route

#### 框架性能表现

测试环境：Ubuntu22.0.4 LTS

测试硬件：虚拟机(VirtualBox) 6c6t , 8192M ,开启虚拟化支持

测试工具：ApacheBench

```
This is ApacheBench, Version 2.3 <$Revision: 1879490 $>
Copyright 1996 Adam Twiss, Zeus Technology Ltd, http://www.zeustech.net/
Licensed to The Apache Software Foundation, http://www.apache.org/

Benchmarking 127.0.0.1 (be patient)


Server Software:        swoole-http-server
Server Hostname:        127.0.0.1
Server Port:            9502

Document Path:          /hello/user/test
Document Length:        20 bytes

Concurrency Level:      3000
Time taken for tests:   2.040 seconds
Complete requests:      30000
Failed requests:        0
Total transferred:      7680000 bytes
HTML transferred:       600000 bytes
Requests per second:    14708.19 [#/sec] (mean)
Time per request:       203.968 [ms] (mean)
Time per request:       0.068 [ms] (mean, across all concurrent requests)
Transfer rate:          3677.05 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0   83  17.3     85     125
Processing:    32  109  41.6    102     380
Waiting:        0   79  40.0     71     362
Total:        107  193  37.8    189     457

Percentage of the requests served within a certain time (ms)
  50%    189
  66%    200
  75%    205
  80%    208
  90%    224
  95%    236
  98%    344
  99%    389
 100%    457 (longest request)

```

注：不要太在意性能，真正的业务逻辑往往是复杂的，对demo进行压测并不能表明什么（图个乐）



> 如果好用可以点个star，如果有问题请提issue，作者会积极维护
>
> 更新于2022-12-28 16:01



### 常见问题

##### git clone下来之后怎么跑不了？

请先运行一遍composer install
