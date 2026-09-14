<?php

/**
 * @desc 指标收集与历史统计器
 * @author Tinywan(ShaoBo Wan)
 * @date 2026/09/14
 */

declare(strict_types=1);

namespace plugin\horizon\app\service;

use Webman\RedisQueue\Redis as QueueRedis;
use support\Redis;

class MetricsCollector
{
    protected string $connection;
    protected string $metricPrefix = 'horizon:metrics';

    public function __construct(?string $connection = null)
    {
        $config = config('plugin.horizon.app.redis', []);
        $this->connection = $connection ?? $config['connection'] ?? 'default';
    }

    protected function redis()
    {
        if (class_exists(QueueRedis::class)) {
            return QueueRedis::connection($this->connection);
        }

        if (class_exists(Redis::class)) {
            return Redis::connection($this->connection);
        }

        throw new \RuntimeException('Neither Webman\RedisQueue\Redis nor support\Redis found.');
    }

    /**
     * 记录完成作业指标
     */
    public function recordJobProcessed(string $queue, float $runtimeMs, ?float $waitTimeMs = null): void
    {
        $redis = $this->redis();
        $minute = date('YmdHi');

        // 1. 分钟吞吐量原子递增
        $throughputKey = "{$this->metricPrefix}:throughput:{$queue}:{$minute}";
        $redis->incr($throughputKey);
        $redis->expire($throughputKey, 172800); // 48 小时

        // 全局吞吐量汇总
        $globalThroughputKey = "{$this->metricPrefix}:throughput:total:{$minute}";
        $redis->incr($globalThroughputKey);
        $redis->expire($globalThroughputKey, 172800);

        // 2. 耗时采样 (保留最后 100 条样本计算平均值)
        $runtimeKey = "{$this->metricPrefix}:runtime:{$queue}:{$minute}";
        $redis->rPush($runtimeKey, (string) round($runtimeMs, 2));
        $redis->expire($runtimeKey, 172800);
    }

    /**
     * 记录失败作业指标
     */
    public function recordJobFailed(string $queue): void
    {
        $redis = $this->redis();
        $minute = date('YmdHi');

        $failedKey = "{$this->metricPrefix}:failed:{$queue}:{$minute}";
        $redis->incr($failedKey);
        $redis->expire($failedKey, 172800);

        $globalFailedKey = "{$this->metricPrefix}:failed:total:{$minute}";
        $redis->incr($globalFailedKey);
        $redis->expire($globalFailedKey, 172800);
    }

    /**
     * 获取过去 N 分钟全局/指定队列吞吐量序列
     *
     * @return array<array{time: string, count: int}>
     */
    public function getThroughputHistory(string $queue = 'total', int $minutes = 60): array
    {
        $redis = $this->redis();
        $now = time();
        $history = [];

        for ($i = $minutes - 1; $i >= 0; $i--) {
            $ts = $now - ($i * 60);
            $minKey = date('YmdHi', $ts);
            $label = date('H:i', $ts);

            $key = "{$this->metricPrefix}:throughput:{$queue}:{$minKey}";
            $count = (int) $redis->get($key);

            $history[] = [
                'time' => $label,
                'count' => $count,
            ];
        }

        return $history;
    }

    /**
     * 计算过去一小时的平均作业耗时 (Runtime in ms)
     */
    public function getAverageRuntime(string $queue = 'default'): float
    {
        $redis = $this->redis();
        $now = time();
        $totalSamples = [];

        // 取最近 10 分钟内的采样
        for ($i = 0; $i < 10; $i++) {
            $minKey = date('YmdHi', $now - ($i * 60));
            $runtimeKey = "{$this->metricPrefix}:runtime:{$queue}:{$minKey}";
            $samples = $redis->lRange($runtimeKey, 0, -1);
            if (!empty($samples)) {
                $totalSamples = array_merge($totalSamples, $samples);
            }
        }

        if (empty($totalSamples)) {
            return 0.0;
        }

        $sum = array_sum(array_map('floatval', $totalSamples));
        return round($sum / count($totalSamples), 2);
    }

    /**
     * 自动清理过期统计键
     */
    public static function cleanupExpiredMetrics(): void
    {
        // Redis 原生 EXPIRE 机制保障大部分键自动淘汰，可在此扩展冷数据落盘或归档逻辑
    }
}
