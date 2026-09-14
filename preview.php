<?php

/**
 * @desc Webman Horizon 本地预览服务器（无需 webman / Redis / Docker）
 *       用 PHP 内置服务器伺服 public/ 预编译产物，并 mock 一套逼真的 API 数据
 * @usage 命令行: php -S 127.0.0.1:8899 preview.php
 *        或直接双击 preview.bat（自动起服务并打开浏览器）
 * @author Tinywan(ShaoBo Wan)
 * @date 2026/09/14
 */

declare(strict_types=1);

$uri = urldecode((string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$method = $_SERVER['REQUEST_METHOD'];
$publicDir = __DIR__ . DIRECTORY_SEPARATOR . 'public';

/* --------------------------------------------------------------------------
 | 1. Mock API：返回结构与 app/controller 各控制器保持一致 {code, msg, data}
 | ------------------------------------------------------------------------ */
if (str_starts_with($uri, '/app/horizon/api/')) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(mockApi($uri, $method), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    return true;
}

/* --------------------------------------------------------------------------
 | 2. 根路径跳转面板
 | ------------------------------------------------------------------------ */
if ($uri === '/' || $uri === '') {
    header('Location: /app/horizon');
    return true;
}

/* --------------------------------------------------------------------------
 | 3. 静态资源（/app/horizon/* -> public/*），非文件路径回退 index.html（SPA）
 | ------------------------------------------------------------------------ */
$relative = preg_replace('#^/app/horizon/?#', '', $uri);
$realBase = realpath($publicDir);
$file = $realBase ? realpath($realBase . DIRECTORY_SEPARATOR . $relative) : false;

if ($file && is_file($file) && str_starts_with($file, (string) $realBase)) {
    serveFile($file);
    return true;
}

// SPA 路由回退（/app/horizon/dashboard 等前端路由直接刷新时）
serveFile($realBase . DIRECTORY_SEPARATOR . 'index.html');
return true;

/* ============================ Mock 数据区 ============================ */

function mockApi(string $uri, string $method): array
{
    $path = substr($uri, strlen('/app/horizon/api'));
    $queue = (string) ($_GET['queue'] ?? 'default');

    return match (true) {
        $path === '/stats' => ok([
            'status' => 'active',
            'queue_count' => 3,
            'total_waiting' => 17,
            'total_delayed' => 2,
            'total_failed' => 3,
            'avg_runtime_ms' => 42.6,
        ]),

        $path === '/throughput' => ok(mockThroughput()),

        $path === '/metrics' => ok([
            'queue' => $queue,
            'avg_runtime_ms' => round(mt_rand(280, 680) / 10, 1),
        ]),

        $path === '/queues' => ok([
            ['queue' => 'default', 'waiting' => 12, 'delayed' => 1, 'failed' => 2],
            ['queue' => 'emails',  'waiting' => 5,  'delayed' => 1, 'failed' => 1],
            ['queue' => 'sms',     'waiting' => 0,  'delayed' => 0, 'failed' => 0],
        ]),

        $path === '/failed-jobs' && $method === 'GET' => ok(mockFailedJobs($queue)),

        $path === '/failed-jobs/retry' => ok(null, 'Job retried successfully (预览模式，未真正执行)'),

        $path === '/failed-jobs/retry-all' => ok(['retried_count' => 3], 'Successfully retried 3 jobs (预览模式)'),

        $path === '/failed-jobs' && $method === 'DELETE' => ok(null, 'Job deleted successfully (预览模式，未真正执行)'),

        $path === '/queues/clear' => ok(null, 'Queue cleared (预览模式)'),

        default => ['code' => 404, 'msg' => "Mock API 未实现: {$method} {$path}", 'data' => null],
    };
}

function ok(mixed $data, string $msg = 'ok'): array
{
    return ['code' => 0, 'msg' => $msg, 'data' => $data];
}

/** 过去 60 分钟吞吐序列：波形 + 轻微随机，看起来像活的 */
function mockThroughput(): array
{
    $points = [];
    for ($i = 59; $i >= 0; $i--) {
        $t = strtotime("-{$i} minutes");
        $wave = (int) round(60 + 40 * sin((60 - $i) / 6) + mt_rand(-8, 12));
        $points[] = ['time' => date('H:i', $t), 'count' => max(0, $wave)];
    }
    return $points;
}

/** 失败任务：按 redis-queue 失败载荷结构构造，支持分页 */
function mockFailedJobs(string $queue): array
{
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $pageSize = max(1, (int) ($_GET['page_size'] ?? 15));

    $classes = [
        'app\queue\MailSendQueue' => 'RuntimeException: SMTP connection timeout (connect() timed out after 10s)',
        'app\queue\OrderSyncQueue' => 'InvalidArgumentException: Order #2026091400123 missing required field `buyer_email`',
        'app\queue\ReportExportQueue' => 'TypeError: array_map(): Argument #2 must be of type array, null given',
    ];

    $total = $queue === 'default' ? 23 : ($queue === 'emails' ? 4 : 0);
    $items = [];
    $start = ($page - 1) * $pageSize;

    for ($i = $start; $i < min($start + $pageSize, $total); $i++) {
        $class = array_keys($classes)[$i % 3];
        $exception = $classes[$class];
        $items[] = [
            'id' => 10000 + $i,
            'index' => $i,
            'queue' => $queue,
            'class' => $class,
            'payload' => [
                'id' => 10000 + $i,
                'class' => $class,
                'data' => ['order_id' => 2026091400000 + $i, 'attempts' => $i % 3 + 1],
                'attempts' => $i % 3 + 1,
                'failed_at' => date('Y-m-d H:i:s', strtotime("-{$i}7 minutes")),
            ],
            'failed_at' => date('Y-m-d H:i:s', strtotime("-{$i}7 minutes")),
            'exception' => $exception . PHP_EOL
                . 'Stack trace:' . PHP_EOL
                . "#0 /var/www/app/queue/Consumer.php(87): {$class}->consume()" . PHP_EOL
                . '#1 /var/www/vendor/webman/redis-queue/src/Process/Consumer.php(66): consume()' . PHP_EOL
                . '#2 {main}',
        ];
    }

    return ['total' => $total, 'page' => $page, 'page_size' => $pageSize, 'items' => $items];
}

/* ============================ 静态文件伺服 ============================ */

function serveFile(string $file): void
{
    static $mimes = [
        'html' => 'text/html; charset=utf-8',
        'js' => 'text/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'map' => 'application/json',
    ];

    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    header('Content-Type: ' . ($mimes[$ext] ?? 'application/octet-stream'));
    header('Content-Length: ' . filesize($file));
    readfile($file);
}
