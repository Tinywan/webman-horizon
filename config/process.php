<?php

/**
 * @desc 自定义进程配置
 * @author Tinywan(ShaoBo Wan)
 * @date 2026/09/14
 */

declare(strict_types=1);

use plugin\horizon\app\process\MetricsProcess;

return [
    'metrics' => [
        'handler' => MetricsProcess::class,
        'count' => 1,
    ],
];
