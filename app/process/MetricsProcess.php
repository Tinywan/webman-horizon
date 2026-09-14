<?php

/**
 * @desc 指标常驻维护进程
 * @author Tinywan(ShaoBo Wan)
 * @date 2026/09/14
 */

declare(strict_types=1);

namespace plugin\horizon\app\process;

use plugin\horizon\app\service\MetricsCollector;
use Workerman\Timer;

class MetricsProcess
{
    /**
     * 进程启动触发
     */
    public function onWorkerStart(): void
    {
        // 每 60 秒执行一次系统指标整理与自检
        Timer::add(60, static function () {
            MetricsCollector::cleanupExpiredMetrics();
        });
    }
}
