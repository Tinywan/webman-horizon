<?php

/**
 * @desc 队列核心管理器
 * @author Tinywan(ShaoBo Wan)
 * @date 2026/09/14
 */

declare(strict_types=1);

namespace plugin\horizon\app\service;

use Webman\RedisQueue\Redis as QueueRedis;
use support\Redis;

class QueueManager
{
    protected string $connection;
    protected string $waitingPrefix;
    protected string $delayedKey;
    protected string $failedKey;

    public function __construct(?string $connection = null, ?string $prefix = null)
    {
        $config = config('plugin.horizon.app.redis', []);
        $this->connection = $connection ?? $config['connection'] ?? 'default';

        // 对齐 webman/redis-queue 底层 Client 常量定义:
        // const QUEUE_WAITING = '{redis-queue}-waiting';
        // const QUEUE_DELAYED = '{redis-queue}-delayed';
        // const QUEUE_FAILED  = '{redis-queue}-failed';
        // 如果配置的是默认的 'redis-queue' 或留空，则直接使用底层的 '{redis-queue}'
        $customPrefix = $prefix ?? $config['prefix'] ?? '';
        if ($customPrefix === 'redis-queue' || $customPrefix === '{redis-queue}' || empty($customPrefix)) {
            $userPrefix = '';
        } else {
            $userPrefix = str_ends_with($customPrefix, '-') ? $customPrefix : $customPrefix . '-';
        }

        $this->waitingPrefix = $userPrefix . '{redis-queue}-waiting';
        $this->delayedKey    = $userPrefix . '{redis-queue}-delayed';
        $this->failedKey     = $userPrefix . '{redis-queue}-failed';
    }

    /**
     * 获取 Redis 客户端实例
     */
    public function redis()
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
     * 探测当前所有活跃队列名称
     *
     * @return array<string>
     */
    public function getDiscoveredQueues(): array
    {
        $redis = $this->redis();
        $queues = [];

        // 1. 扫描 waiting 队列: {redis-queue}-waiting*
        $waitingKeys = $redis->keys($this->waitingPrefix . '*');
        if (!empty($waitingKeys)) {
            foreach ($waitingKeys as $key) {
                // 提取队列名称
                $pos = strpos($key, $this->waitingPrefix);
                if ($pos !== false) {
                    $queueName = substr($key, $pos + strlen($this->waitingPrefix));
                    if ($queueName !== '') {
                        $queues[$queueName] = true;
                    }
                }
            }
        }

        // 2. 从全局 delayed 队列中解析业务队列名 (扫描前 200 条延迟任务)
        $delayedJobs = $redis->zRange($this->delayedKey, 0, 200);
        if (!empty($delayedJobs)) {
            foreach ($delayedJobs as $raw) {
                $pkg = json_decode($raw, true);
                if (!empty($pkg['queue'])) {
                    $queues[$pkg['queue']] = true;
                }
            }
        }

        // 3. 从全局 failed 队列中解析业务队列名 (扫描前 200 条失败任务)
        $failedJobs = $redis->lRange($this->failedKey, 0, 200);
        if (!empty($failedJobs)) {
            foreach ($failedJobs as $raw) {
                $pkg = json_decode($raw, true);
                if (!empty($pkg['queue'])) {
                    $queues[$pkg['queue']] = true;
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
        return (int) $this->redis()->lLen("{$this->waitingPrefix}{$queue}");
    }

    /**
     * 获取指定队列延迟任务数 (Delayed)
     */
    public function getDelayedCount(string $queue): int
    {
        $redis = $this->redis();
        $allDelayed = $redis->zRange($this->delayedKey, 0, -1);
        if (empty($allDelayed)) {
            return 0;
        }

        $count = 0;
        foreach ($allDelayed as $raw) {
            $pkg = json_decode($raw, true);
            if (isset($pkg['queue']) && $pkg['queue'] === $queue) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * 获取指定队列失败任务数 (Failed)
     */
    public function getFailedCount(string $queue): int
    {
        $redis = $this->redis();
        $allFailed = $redis->lRange($this->failedKey, 0, -1);
        if (empty($allFailed)) {
            return 0;
        }

        $count = 0;
        foreach ($allFailed as $raw) {
            $pkg = json_decode($raw, true);
            if (isset($pkg['queue']) && $pkg['queue'] === $queue) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * 获取单个队列详细快照
     */
    public function getQueueDetails(string $queue): array
    {
        $waiting = $this->getWaitingCount($queue);
        $delayed = $this->getDelayedCount($queue);
        $failed = $this->getFailedCount($queue);

        return [
            'queue' => $queue,
            'waiting' => $waiting,
            'delayed' => $delayed,
            'failed' => $failed,
            'total' => $waiting + $delayed,
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
        $redis = $this->redis();

        if ($type === 'waiting') {
            $redis->del("{$this->waitingPrefix}{$queue}");
            return true;
        }

        if ($type === 'delayed') {
            $items = $redis->zRange($this->delayedKey, 0, -1);
            foreach ($items as $raw) {
                $pkg = json_decode($raw, true);
                if (isset($pkg['queue']) && $pkg['queue'] === $queue) {
                    $redis->zRem($this->delayedKey, $raw);
                }
            }
            return true;
        }

        if ($type === 'failed') {
            $items = $redis->lRange($this->failedKey, 0, -1);
            foreach ($items as $raw) {
                $pkg = json_decode($raw, true);
                if (isset($pkg['queue']) && $pkg['queue'] === $queue) {
                    $redis->lRem($this->failedKey, $raw, 1);
                }
            }
            return true;
        }

        return false;
    }

    /**
     * 获取失败任务列表（分页）
     */
    public function getFailedJobs(string $queue = 'default', int $page = 1, int $pageSize = 15): array
    {
        $redis = $this->redis();
        $allFailed = $redis->lRange($this->failedKey, 0, -1);

        $filtered = [];
        foreach ($allFailed as $index => $raw) {
            $decoded = json_decode($raw, true) ?: [];
            if (empty($queue) || (isset($decoded['queue']) && $decoded['queue'] === $queue)) {
                $filtered[] = [
                    'id' => $decoded['id'] ?? (string) $index,
                    'index' => $index,
                    'queue' => $decoded['queue'] ?? $queue,
                    'class' => $decoded['class'] ?? $decoded['data']['commandName'] ?? 'UnknownJob',
                    'payload' => $decoded,
                    'failed_at' => isset($decoded['time']) ? date('Y-m-d H:i:s', (int) $decoded['time']) : null,
                    'exception' => $decoded['error'] ?? $decoded['exception'] ?? null,
                ];
            }
        }

        $total = count($filtered);
        $start = ($page - 1) * $pageSize;
        $items = array_slice($filtered, $start, $pageSize);

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
        $raw = $redis->lIndex($this->failedKey, $index);
        if (!$raw) {
            return false;
        }

        $pkg = json_decode($raw, true);
        $targetQueue = $pkg['queue'] ?? $queue;
        $waitingKey = "{$this->waitingPrefix}{$targetQueue}";

        // 推回对应 waiting 队列
        $redis->rPush($waitingKey, $raw);
        // 从全局 failed 队列删除此项
        $redis->lRem($this->failedKey, $raw, 1);

        return true;
    }

    /**
     * 重试指定队列所有失败任务
     */
    public function retryAllFailedJobs(string $queue): int
    {
        $redis = $this->redis();
        $allFailed = $redis->lRange($this->failedKey, 0, -1);

        $count = 0;
        foreach ($allFailed as $raw) {
            $pkg = json_decode($raw, true);
            if (isset($pkg['queue']) && $pkg['queue'] === $queue) {
                $waitingKey = "{$this->waitingPrefix}{$queue}";
                $redis->rPush($waitingKey, $raw);
                $redis->lRem($this->failedKey, $raw, 1);
                $count++;
            }
        }

        return $count;
    }

    /**
     * 删除单条失败记录
     */
    public function deleteFailedJob(string $queue, int $index): bool
    {
        $redis = $this->redis();
        $raw = $redis->lIndex($this->failedKey, $index);
        if (!$raw) {
            return false;
        }

        $redis->lRem($this->failedKey, $raw, 1);
        return true;
    }

    /**
     * 获取单条失败任务详情
     */
    public function getFailedJob(string $queue, int $index): ?array
    {
        $redis = $this->redis();
        $raw = $redis->lIndex($this->failedKey, $index);
        if (!$raw) {
            return null;
        }

        $decoded = json_decode($raw, true) ?: [];

        return [
            'id' => $decoded['id'] ?? (string) $index,
            'index' => $index,
            'queue' => $decoded['queue'] ?? $queue,
            'class' => $decoded['class'] ?? $decoded['data']['commandName'] ?? 'UnknownJob',
            'payload' => $decoded,
            'failed_at' => isset($decoded['time']) ? date('Y-m-d H:i:s', (int) $decoded['time']) : null,
            'exception' => $decoded['error'] ?? $decoded['exception'] ?? null,
        ];
    }
}
