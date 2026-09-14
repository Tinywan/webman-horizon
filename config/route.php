<?php

/**
 * @desc 路由配置
 * @author Tinywan(ShaoBo Wan)
 * @date 2026/09/14
 */

declare(strict_types=1);

use plugin\horizon\app\controller\FailedJobController;
use plugin\horizon\app\controller\IndexController;
use plugin\horizon\app\controller\QueueController;
use plugin\horizon\app\controller\StatsController;
use plugin\horizon\app\middleware\AuthMiddleware;
use Webman\Route;

// 1. 监控面板后端 RESTful API（必须先于 SPA 通配路由注册，避免被遮蔽）
Route::group('/app/horizon/api', function () {
    // 总体指标统计
    Route::get('/stats', [StatsController::class, 'stats']);
    // 吞吐量历史序列 (图表使用)
    Route::get('/throughput', [StatsController::class, 'throughput']);
    // 运行时长与等待时长指标
    Route::get('/metrics', [StatsController::class, 'metrics']);

    // 队列列表与管理
    Route::get('/queues', [QueueController::class, 'index']);
    Route::post('/queues/clear', [QueueController::class, 'clear']);

    // 失败任务管理
    Route::get('/failed-jobs', [FailedJobController::class, 'index']);
    Route::get('/failed-jobs/{id}', [FailedJobController::class, 'show']);
    Route::post('/failed-jobs/retry', [FailedJobController::class, 'retry']);
    Route::post('/failed-jobs/retry-all', [FailedJobController::class, 'retryAll']);
    Route::delete('/failed-jobs', [FailedJobController::class, 'delete']);
})->middleware([
    AuthMiddleware::class,
]);

// 2. 监控面板 SPA 页面入口及前端子路由支持（必须排除 api 前缀，避免遮蔽 API 路由）
Route::get('/app/horizon', [IndexController::class, 'index']);
Route::get('/app/horizon/{path:^(?!api/).*$}', [IndexController::class, 'index']);
