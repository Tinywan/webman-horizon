<?php

/**
 * @desc 仪表盘入口控制器
 * @author Tinywan(ShaoBo Wan)
 * @date 2026/09/14
 */

declare(strict_types=1);

namespace plugin\horizon\app\controller;

use support\Request;
use support\Response;

class IndexController
{
    /**
     * 响应 SPA 静态入口页面
     */
    public function index(Request $request): Response
    {
        $indexPath = base_path('plugin/horizon/public/index.html');

        // 如果在独立仓库中开发，回退到当前目录下的 public/index.html
        if (!file_exists($indexPath)) {
            $indexPath = dirname(__DIR__, 2) . '/public/index.html';
        }

        if (file_exists($indexPath)) {
            return response(file_get_contents($indexPath))->withHeaders([
                'Content-Type' => 'text/html; charset=utf-8',
            ]);
        }

        return response('Webman Horizon: Frontend assets not found. Please build the frontend first.', 404);
    }
}
