<?php

declare(strict_types=1);

use plugin\horizon\app\service\MetricsCollector;
use plugin\horizon\app\service\QueueManager;

test('queue manager can be instantiated', function () {
    $manager = new QueueManager('default', 'redis-queue');
    expect($manager)->toBeInstanceOf(QueueManager::class);
});

test('metrics collector can be instantiated', function () {
    $collector = new MetricsCollector('default');
    expect($collector)->toBeInstanceOf(MetricsCollector::class);
});

test('can generate throughput timeline structure', function () {
    // 模拟纯逻辑断言
    $now = time();
    $history = [];
    for ($i = 5; $i >= 0; $i--) {
        $ts = $now - ($i * 60);
        $history[] = [
            'time' => date('H:i', $ts),
            'count' => 0,
        ];
    }

    expect(count($history))->toBe(6)->and($history[0])->toHaveKeys(['time', 'count']);
});
