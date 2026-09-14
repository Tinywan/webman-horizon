<?php

/**
 * @desc 基础消费者抽象类（可选，用于业务更便捷的继承与使用）
 * @author Tinywan(ShaoBo Wan)
 * @date 2026/09/14
 */

declare(strict_types=1);

namespace plugin\horizon\app\service;

use Webman\RedisQueue\Consumer;

abstract class BaseConsumer implements Consumer
{
    /**
     * 要消费的队列名称，由子类定义
     */
    public string $queue = 'default';

    /**
     * 连接名称，对应 redis-queue.redis 配置
     */
    public string $connection = 'default';

    /**
     * 具体的业务消费逻辑，由子类实现
     *
     * @param mixed $data 队列消息体
     */
    abstract public function handle(mixed $data): void;

    /**
     * 默认消费包装：自动精准计时并上报 Horizon
     */
    public function consume($data): void
    {
        $collector = new MetricsCollector();
        $startTime = microtime(true);

        try {
            $this->handle($data);
            $costMs = (microtime(true) - $startTime) * 1000;
            $collector->recordJobProcessed($this->queue, $costMs);
        } catch (\Throwable $e) {
            $collector->recordJobFailed($this->queue);
            throw $e;
        }
    }
}
