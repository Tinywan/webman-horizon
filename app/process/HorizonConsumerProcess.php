<?php

/**
 * @desc Horizon 全局无感队列消费代理进程（自动记录执行耗时与吞吐量指标）
 * @author Tinywan(ShaoBo Wan)
 * @date 2026/09/14
 */

declare(strict_types=1);

namespace plugin\horizon\app\process;

use plugin\horizon\app\service\MetricsCollector;
use support\Container;
use Webman\RedisQueue\Client;
use Webman\RedisQueue\Process\Consumer;

class HorizonConsumerProcess extends Consumer
{
    /**
     * 重写 onWorkerStart：包装消费回调，实现 100% 自动无感指标采集
     */
    public function onWorkerStart(): void
    {
        if (!is_dir($this->_consumerDir)) {
            echo "Consumer directory {$this->_consumerDir} not exists\r\n";
            return;
        }

        $metricsCollector = new MetricsCollector();
        $dirIterator = new \RecursiveDirectoryIterator($this->_consumerDir);
        $iterator = new \RecursiveIteratorIterator($dirIterator);

        foreach ($iterator as $file) {
            if (is_dir($file->getPathname())) {
                continue;
            }

            if ($file->getExtension() === 'php') {
                $class = str_replace('/', '\\', substr(substr($file->getPathname(), strlen(base_path())), 0, -4));
                if (is_a($class, 'Webman\RedisQueue\Consumer', true)) {
                    $consumer = Container::get($class);
                    $connectionName = $consumer->connection ?? 'default';
                    $queue = $consumer->queue;
                    if (!$queue) {
                        echo "Consumer {$class} queue not exists\r\n";
                        continue;
                    }

                    $this->_consumers[$queue] = $consumer;
                    $connection = Client::connection($connectionName);

                    // 包装消费者回调：自动精确计时并上报 Horizon
                    $wrappedCallback = function ($data) use ($consumer, $queue, $metricsCollector) {
                        $startTime = microtime(true);
                        try {
                            $consumer->consume($data);
                            $runtimeMs = (microtime(true) - $startTime) * 1000;
                            $metricsCollector->recordJobProcessed($queue, $runtimeMs);
                        } catch (\Throwable $e) {
                            $metricsCollector->recordJobFailed($queue);
                            throw $e;
                        }
                    };

                    $connection->subscribe($queue, $wrappedCallback);

                    if (method_exists($connection, 'onConsumeFailure')) {
                        $connection->onConsumeFailure(function ($exception, $package) {
                            $consumer = $this->_consumers[$package['queue']] ?? null;
                            if ($consumer && method_exists($consumer, 'onConsumeFailure')) {
                                return call_user_func([$consumer, 'onConsumeFailure'], $exception, $package);
                            }
                        });
                    }
                }
            }
        }
    }
}
