# Watish WEB
### 一个基于swoole驱动的多进程协程Http服务框架
#### 技术栈
Swoole，PHP

#### 框架特点
+ 支持websocket
+ 通过unixsocket实现多进程间的全局变量一致
+ 支持独立进程Process
+ 支持Crontab定时任务
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



### 上下文管理 Context

不同于传统的php-fpm形式，**多进程之间存在内存隔离**，这意味着在进程A设定的变量进程B是无法获取的，此外，**请求与请求之间并不是隔离的**，也就是说，在同一进程下的两个请求，尽管在不同的协程中处理逻辑，如果都对全局变量A修改，那么全局变量会被修改两次

具体可查阅swoole文档中的 [**编程须知**#严重错误](https://wiki.swoole.com/#/coroutine/notice?id=%e4%b8%a5%e9%87%8d%e9%94%99%e8%af%af)

使用 **Watish\Components\Includes\Context** 可以有效规避上述问题

**Context**是一个静态类，不仅提供了简单的**Get**，**Set**方法，还通过进程通信提供了**多worker进程**全局变量的GlobalSet，GlobalGet等方法

注：多worker进程全局变量仅适用于广播通信的业务场景，请勿重度依赖GlobalSet等基于多进程通信统一的方法，如需高并发，数据强一致请使用 **Watish\Components\Utils\Table** ，一个对 **Swoole\Table** 的封装，可以充分利用每一行资源，并支持闭包序列化

### 请求 Request

当浏览器发送请求至服务器，服务器会调用handle方法，随后通过路由调度器判断请求路由是否存在，存在解析路由参数，封装至 **Watish\Components\Struct\Request**，传入 **全局中间件 -> 局部中间件 -> 控制器**
