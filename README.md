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
```
git clone https://github.com/Watish/WatishWEB
```

#### 使用Composer
```
composer create-project watish/watishweb:dev-master
```

