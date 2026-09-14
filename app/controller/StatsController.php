<?php

/**
 * @desc 指标与概览统计控制器
 * @author Tinywan(ShaoBo Wan)
 * @date 2026/09/14
 */

declare(strict_types=1);

namespace plugin\horizon\app\controller;

use plugin\horizon\app\service\MetricsCollector;
use plugin\horizon\app\service\QueueManager;
use support\Request;
use support\Response;

class StatsController
{
    protected QueueManager $queueManager;
    protected MetricsCollector $metricsCollector;

    public function __construct()
    {
        $this->queueManager = new QueueManager();
        $this->metricsCollector = new MetricsCollector();
    }

    /**
     * 全局状态卡片概览
     */
    public function stats(Request $request): Response
    {
        $queues = $this->queueManager->getAllQueueDetails();

        $totalWaiting = array_sum(array_column($queues, 'waiting'));
        $totalDelayed = array_sum(array_column($queues, 'delayed'));
        $totalFailed = array_sum(array_column($queues, 'failed'));

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'status' => 'active', // Horizon 运行状态
                'queue_count' => count($queues),
                'total_waiting' => $totalWaiting,
                'total_delayed' => $totalDelayed,
                'total_failed' => $totalFailed,
                'avg_runtime_ms' => $this->metricsCollector->getAverageRuntime('default'),
            ],
        ]);
    }

    /**
     * 过去 60 分钟每分钟吞吐量序列
     */
    public function throughput(Request $request): Response
    {
        $queue = (string) $request->get('queue', 'total');
        $minutes = (int) $request->get('minutes', 60);

        $history = $this->metricsCollector->getThroughputHistory($queue, $minutes);

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => $history,
        ]);
    }

    /**
     * 运行时长指标
     */
    public function metrics(Request $request): Response
    {
        $queue = (string) $request->get('queue', 'default');
        $runtime = $this->metricsCollector->getAverageRuntime($queue);

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => [
                'queue' => $queue,
                'avg_runtime_ms' => $runtime,
            ],
        ]);
    }
}
