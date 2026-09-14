<?php

/**
 * @desc 队列核心管理器
 * @author Tinywan(ShaoBo Wan)
 * @date 2026/09/14
 */

declare(strict_types=1);

namespace plugin\horizon\app\service;

use support\Redis;

class QueueManager
{
    protected string $connection;
    protected string $prefix;

    public function __construct(?string $connection = null, ?string $prefix = null)
    {
        $config = config('plugin.horizon.app.redis', []);
        $this->connection = $connection ?? $config['connection'] ?? 'default';
        $this->prefix = $prefix ?? $config['prefix'] ?? 'redis-queue';
    }

    /**
     * 获取 Redis 客户端实例
     */
    protected function redis()
    {
        return Redis::connection($this->connection);
    }

    /**
     * 探测当前所有活跃队列名称
     *
     * @return array<string>
     */
    public function getDiscoveredQueues(): array
    {
        $redis = $this->redis();
        $queues = [];

        // 检索 waiting/delayed/failed 等 key
        $patterns = [
            "{$this->prefix}-waiting:*",
            "{$this->prefix}-delayed:*",
            "{$this->prefix}-failed:*",
        ];

        foreach ($patterns as $pattern) {
            $keys = $redis->keys($pattern);
            if (!empty($keys)) {
                foreach ($keys as $key) {
                    // 去除可能的前缀包装与命名空间
                    if (preg_match('/(?:waiting|delayed|failed):(.+)$/', $key, $matches)) {
                        $queues[$matches[1]] = true;
                    }
                }
            }
        }

        // 始终保留至少一个 default
        if (empty($queues)) {
            $queues['default'] = true;
        }

        return array_keys($queues);
    }

    /**
     * 获取指定队列等待处理任务数 (Waiting)
     */
    public function getWaitingCount(string $queue): int
    {
        return (int) $this->redis()->lLen("{$this->prefix}-waiting:{$queue}");
    }

    /**
     * 获取指定队列延迟任务数 (Delayed)
     */
    public function getDelayedCount(string $queue): int
    {
        return (int) $this->redis()->zCard("{$this->prefix}-delayed:{$queue}");
    }

    /**
     * 获取指定队列失败任务数 (Failed)
     */
    public function getFailedCount(string $queue): int
    {
        return (int) $this->redis()->lLen("{$this->prefix}-failed:{$queue}");
    }

    /**
     * 获取单个队列详细快照
     */
    public function getQueueDetails(string $queue): array
    {
        return [
            'queue' => $queue,
            'waiting' => $this->getWaitingCount($queue),
            'delayed' => $this->getDelayedCount($queue),
            'failed' => $this->getFailedCount($queue),
            'total' => $this->getWaitingCount($queue) + $this->getDelayedCount($queue),
        ];
    }

    /**
     * 获取所有队列状态列表
     */
    public function getAllQueueDetails(): array
    {
        $queues = $this->getDiscoveredQueues();
        $list = [];

        foreach ($queues as $queue) {
            $list[] = $this->getQueueDetails($queue);
        }

        return $list;
    }

    /**
     * 清空指定队列数据
     */
    public function clearQueue(string $queue, string $type = 'waiting'): bool
    {
        $validTypes = ['waiting', 'delayed', 'failed'];
        if (!in_array($type, $validTypes, true)) {
            return false;
        }

        $key = "{$this->prefix}-{$type}:{$queue}";
        $this->redis()->del($key);
        return true;
    }

    /**
     * 获取失败任务列表（分页）
     */
    public function getFailedJobs(string $queue = 'default', int $page = 1, int $pageSize = 15): array
    {
        $redis = $this->redis();
        $key = "{$this->prefix}-failed:{$queue}";

        $total = (int) $redis->lLen($key);
        $start = ($page - 1) * $pageSize;
        $end = $start + $pageSize - 1;

        $items = [];
        if ($total > 0 && $start < $total) {
            $rawList = $redis->lRange($key, $start, $end);
            foreach ($rawList as $index => $raw) {
                $decoded = json_decode($raw, true) ?: [];
                $items[] = [
                    'id' => $decoded['id'] ?? ($start + $index),
                    'index' => $start + $index,
                    'queue' => $queue,
                    'class' => $decoded['class'] ?? $decoded['data']['commandName'] ?? 'UnknownJob',
                    'payload' => $decoded,
                    'failed_at' => $decoded['failed_at'] ?? $decoded['time'] ?? null,
                    'exception' => $decoded['exception'] ?? $decoded['error'] ?? null,
                ];
            }
        }

        return [
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'items' => $items,
        ];
    }

    /**
     * 重试单条失败任务
     */
    public function retryFailedJob(string $queue, int $index): bool
    {
        $redis = $this->redis();
        $failedKey = "{$this->prefix}-failed:{$queue}";
        $waitingKey = "{$this->prefix}-waiting:{$queue}";

        $raw = $redis->lIndex($failedKey, $index);
        if (!$raw) {
            return false;
        }

        // 推回 waiting 队列
        $redis->rPush($waitingKey, $raw);
        // 从 failed 队列删除此任务
        $redis->lRem($failedKey, $raw, 1);

        return true;
    }

    /**
     * 重试指定队列所有失败任务
     */
    public function retryAllFailedJobs(string $queue): int
    {
        $redis = $this->redis();
        $failedKey = "{$this->prefix}-failed:{$queue}";
        $waitingKey = "{$this->prefix}-waiting:{$queue}";

        $count = 0;
        while (true) {
            $job = $redis->rPop($failedKey);
            if (!$job) {
                break;
            }
            $redis->rPush($waitingKey, $job);
            $count++;
        }

        return $count;
    }

    /**
     * 删除单条失败记录
     */
    public function deleteFailedJob(string $queue, int $index): bool
    {
        $redis = $this->redis();
        $failedKey = "{$this->prefix}-failed:{$queue}";

        $raw = $redis->lIndex($failedKey, $index);
        if (!$raw) {
            return false;
        }

        $redis->lRem($failedKey, $raw, 1);
        return true;
    }
}
