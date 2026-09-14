# Webman Horizon (Redis 队列监控面板)

<p align="center">
  <a href="https://packagist.org/packages/tinywan/webman-horizon"><img src="https://img.shields.io/packagist/v/tinywan/webman-horizon?color=blue&label=version" alt="Latest Version"></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/php-%3E%3D8.2-8892BF.svg" alt="PHP Version"></a>
  <a href="https://www.workerman.net/doc/webman/app/app.html"><img src="https://img.shields.io/badge/webman-app--plugin-success.svg" alt="Webman App Plugin"></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-brightgreen.svg" alt="License"></a>
</p>

## 简介

**Webman Horizon** 是专为 [Webman](https://www.workerman.net/doc/webman/) 生态打造的高性能、高颜值 **Redis 队列监控面板与指标统计系统**（对标 Laravel Horizon）。

严格遵循 [Webman 官方应用插件（App Plugin）开发规范](https://www.workerman.net/doc/webman/app/app.html)，拥有独立完整的命名空间、路由、控制器、中间件、自定义常驻维护进程与静态资源体系。像搭建积木一样即插即用，**开箱即用，零前端构建依赖**。


## 核心特性

- 📊 **现代化暗黑风控制台**：基于 Vue 3 + TailwindCSS + Apache ECharts 构建，完整还原 Laravel Horizon 经典暗黑质感与 6 大核心屏（Dashboard、Monitoring、Metrics、Recent Jobs、Failed Jobs、Batches）。
- ⚡ **零配置开箱即用（Zero-Config）**：随包携带预编译静态资源，安装即可访问，使用者机器**无需安装 Node.js、Vite 或任何前端环境**。
- 📈 **分钟级吞吐量与耗时走势**：通过 Redis 原子自增与采样，统计过去 60 分钟每分钟队列完成量波形图，以及作业单次运行平均耗时（Runtime）。
- 🔄 **队列健康与积压全景图**：实时探测所有活跃队列，展示等待消费量（Waiting）、延迟就绪量（Delayed）和失败堆积量（Failed）。
- 🛠️ **完善的失败作业中心**：完整还原异常错误信息与完整调用堆栈（Stack Trace），支持单任务重试、全量一键重试与安全删除。
- 🛡️ **内置安全访问控制**：内置 HTTP Basic Auth 认证中间件，支持灵活开关与密码防护。
- 🚀 **官方应用插件标准**：
  - 应用标识：`horizon`
  - 命名空间：`plugin\horizon\`
  - 访问路径：`/app/horizon`（官方推荐规范）
  - 提供 `api/Install.php`，支持接入 Webman 官方应用市场一键安装与卸载。


## 依赖环境

- PHP >= 8.2
- Workerman / Webman Framework >= 1.4 / 2.0
- [webman/redis-queue](https://github.com/webman-php/redis-queue) >= 1.0


## 安装与使用

### 1. Composer 一键安装

在 Webman 项目根目录下执行：

```bash
composer require tinywan/webman-horizon
```

安装完成后，Webman 会自动将插件配置文件与静态资源初始化到主项目的 `plugin/horizon/` 目录下。

### 2. 访问监控面板

启动 Webman 服务：

```bash
php start.php start
# 或使用调试模式
php start.php start -d
# Windows 环境
php windows.php
```

打开浏览器直接访问（符合 Webman 官方应用插件标准规范）：

```text
http://127.0.0.1:8787/app/horizon
```


## 核心配置说明

### 1. 基础配置文件 `plugin/horizon/config/app.php`

```php
return [
    // 插件总开关
    'enable' => true,

    // 调试模式 (必须为 bool 类型，供 Webman 依赖注入解析器使用)
    'debug' => true,

    // 面板访问入口路径 (默认为 /app/horizon，可自定义如 /horizon)
    'path' => '/app/horizon',

    // 绑定域名 (可选，留空 null 表示不限制域名)
    'domain' => null,

    // 访问鉴权 (生产环境强烈建议开启)
    'auth' => [
        'enabled' => false,       // 是否开启 HTTP Basic 认证
        'username' => 'admin',    // 登录用户名
        'password' => 'admin123', // 登录密码
    ],

    // Redis 队列驱动连接与前缀
    'redis' => [
        'connection' => 'default', // 对应 config/plugin/webman/redis-queue/redis.php 中的连接名
        'prefix' => 'redis-queue', // 队列前缀 (默认留空或 redis-queue 即可，与 webman/redis-queue 保持一致)
    ],

    // 指标采集与保留配置
    'metrics' => [
        'trim_snapshots' => [
            'recent' => 2880,      // 分钟级采样数据保留时长 (分钟，默认48小时自动淘汰)
        ],
    ],
];
```


### 2. 开启 100% 全局无感吞吐量与耗时统计（强烈推荐）

想要在 Horizon 面板中看到**吞吐量走势图（Throughput）**与**作业单次执行平均耗时（Runtime）**，消费者需要在执行时进行微秒级耗时采样。

Webman Horizon 提供了内置的无感消费者代理进程 `HorizonConsumerProcess`，**无需修改任何业务代码**，仅需调整一行配置即可全自动启用！

修改主项目的消费者进程配置：`config/plugin/webman/redis-queue/process.php`：

```php
return [
    'consumer' => [
        // 将原生的 Consumer::class 替换为 Horizon 提供的代理进程：
        'handler' => plugin\horizon\app\process\HorizonConsumerProcess::class,
        'count' => 8, // 消费者进程数
        'constructor' => [
            // 消费者类所在目录
            'consumer_dir' => app_path() . '/queue/redis',
        ],
    ],
];
```

> **效果**：
> 配置后，所有放在 `app/queue/redis/` 下的普通消费者在消费成功或失败时，都会被自动精确计时并上报给 Horizon，面板的**吞吐量折线图**与**实时每分钟作业数**将全自动绘制！


### 3. 可选方式：通过继承基类开发消费者

如果不方便替换全局消费者进程配置，也可以让单个消费者类继承 Horizon 内置的 `BaseConsumer`，同样免写任何打点代码：

```php
namespace app\queue\redis;

use plugin\horizon\app\service\BaseConsumer;

class OrderConsumer extends BaseConsumer
{
    // 监听的队列名
    public string $queue = 'order-process';

    // 监听的 Redis 连接 (对应 redis-queue.php)
    public string $connection = 'default';

    /**
     * 只需要实现 handle 方法，执行完毕会自动统计耗时与成功/失败指标
     */
    public function handle($data): void
    {
        // 编写纯业务逻辑
        echo "正在处理订单: " . json_encode($data);
    }
}
```


### 4. 静态资源配置文件 `plugin/horizon/config/static.php`

应用插件的静态文件服务配置（HTML / CSS / JS 等打包产物）：

```php
return [
    // 开启插件静态资源支持 (默认 true，否则访问静态资源会报 404)
    'enable' => true,
    'middleware' => [],
];
```


## 插件应用规范与目录结构

本项目完全符合 [Webman 应用插件开发规范与目录标准](https://www.workerman.net/doc/webman/app/standard.html)：

```text
plugin/horizon/
├── api/
│   └── Install.php                  # 应用市场与 Composer 安装/卸载联动脚本
├── app/
│   ├── controller/                  # 应用控制器 (Index / Stats / Queue / FailedJob)
│   ├── middleware/                  # 鉴权安全中间件 (AuthMiddleware)
│   ├── process/                     # 全局无感消费代理进程 (HorizonConsumerProcess)
│   ├── service/                     # QueueManager 队列核心服务 & MetricsCollector 指标采集器
│   └── functions.php                # 辅助函数库
├── config/                          # 插件独立配置 (app.php / route.php / static.php)
├── public/                          # 预编译静态前端产物 (Webman 原生映射托管)
├── resources/                       # Vue 3 前端工程源码 (供二次开发与定制)
├── composer.json                    # Composer 与应用市场规范元信息
└── README.md
```


## 本地快速体验与二次开发

如果您想要在未启动 Webman / Redis 的环境下快速预览或调试前端 UI：

### 快速预览（免 Webman / 免 Redis）
直接运行内置的 Mock 预览服务：
```bash
# Windows 环境直接双击 preview.bat
# 或执行：
php -S 127.0.0.1:8899 preview.php
```
浏览器打开 `http://127.0.0.1:8899/app/horizon` 即可体验包含逼真动态正弦波形图与异常调用栈的完整控制台。

### 前端二次开发与构建
```bash
npm install
npm run dev      # 本地 Vite 热更新调试
npm run build    # 重新构建并自动输出到 public/
```

### 运行单元测试与代码质检
```bash
# 运行 Pest 单元测试
composer run test

# 运行 Mago 代码格式化与规范检查
composer run format:check
composer run lint
```


## 开源协议

本项目基于 [MIT 协议](LICENSE) 开源。
