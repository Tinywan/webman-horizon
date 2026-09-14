<?php

/**
 * @desc 基础配置
 * @author Tinywan(ShaoBo Wan)
 * @date 2026/09/14
 */

declare(strict_types=1);

return [
    'enable' => true,
    'path' => '/app/horizon', // 面板访问根路径
    'domain' => null,

    // 基础鉴权（可配置账号密码，留空则免密）
    'auth' => [
        'enabled' => false,
        'username' => 'admin',
        'password' => 'admin123',
    ],

    // 默认监听的 Redis 队列连接与前缀
    'redis' => [
        'connection' => 'default',
        'prefix' => 'redis-queue',
    ],

    // 指标采集与保留配置
    'metrics' => [
        'trim_snapshots' => [
            'recent' => 2880, // 分钟级采样保留时长 (分钟，默认48小时)
        ],
    ],
];
