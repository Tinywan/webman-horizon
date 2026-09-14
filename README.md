# Webman Horizon (Redis 队列监控面板)

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/horizon/11.x/art/readme-showcase.png" alt="Webman Horizon" width="800">
</p>

## 简介

**Webman Horizon** 是专为 [Webman](https://www.workerman.net/doc/webman/) 生态打造的高颜值、高性能 **Redis 队列监控面板与运行指标管理系统**（对标 Laravel Horizon）。

遵循 Webman 官方最新的**应用插件（App Plugin）**开发规范，即插即用，零前端构建依赖。

---

## 核心特性

- 📊 **现代化暗黑风仪表盘**：Vue 3 + TailwindCSS + Apache ECharts 构建的高颜值监控界面。
- ⚡ **开箱即用（Zero-Config）**：内嵌预编译前端，安装即可访问，无需在本地配置 Node.js 或构建工具。
- 📈 **实时吞吐量与耗时统计**：分钟级吞吐波形图展示、任务单次运行时长（Runtime）采样。
- 🔄 **队列健康与积压监控**：实时查看各队列等待任务（Waiting）、延迟任务（Delayed）和失败任务（Failed）。
- 🛠️ **失败任务中心**：完整记录异常堆栈，支持单条重试、一键全量重试以及安全清空。
- 🛡️ **访问安全控制**：内置 HTTP Basic Auth 鉴权中间件。

---

## 安装与快速上手

### 1. 安装插件

```bash
composer require tinywan/webman-horizon
```

### 2. 访问监控面板

启动 Webman 服务：
```bash
php start.php start
```

在浏览器中直接访问：
```text
http://127.0.0.1:8787/app/horizon
```

---

## 配置说明

配置文件位于：`config/plugin/horizon/app.php`

```php
return [
    'enable' => true,
    'path' => '/app/horizon',

    // 访问鉴权 (默认关闭，生产环境建议开启)
    'auth' => [
        'enabled' => false,
        'username' => 'admin',
        'password' => 'admin123',
    ],

    // Redis 连接
    'redis' => [
        'connection' => 'default',
        'prefix' => 'redis-queue',
    ],
];
```

---

## 开源协议

MIT License.
