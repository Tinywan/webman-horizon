<?php

/**
 * @desc 失败任务管理控制器
 * @author Tinywan(ShaoBo Wan)
 * @date 2026/09/14
 */

declare(strict_types=1);

namespace plugin\horizon\app\controller;

use plugin\horizon\app\service\QueueManager;
use support\Request;
use support\Response;

class FailedJobController
{
    protected QueueManager $queueManager;

    public function __construct()
    {
        $this->queueManager = new QueueManager();
    }

    /**
     * 分页获取失败任务列表
     */
    public function index(Request $request): Response
    {
        $queue = (string) $request->get('queue', 'default');
        $page = (int) $request->get('page', 1);
        $pageSize = (int) $request->get('page_size', 15);

        $result = $this->queueManager->getFailedJobs($queue, $page, $pageSize);

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => $result,
        ]);
    }

    /**
     * 重试单条任务
     */
    public function retry(Request $request): Response
    {
        $queue = (string) $request->post('queue', 'default');
        $index = (int) $request->post('index', 0);

        $success = $this->queueManager->retryFailedJob($queue, $index);

        return json([
            'code' => $success ? 0 : 1,
            'msg' => $success ? 'Job retried successfully' : 'Failed to retry job',
        ]);
    }

    /**
     * 重试某队列全部失败任务
     */
    public function retryAll(Request $request): Response
    {
        $queue = (string) $request->post('queue', 'default');

        $count = $this->queueManager->retryAllFailedJobs($queue);

        return json([
            'code' => 0,
            'msg' => "Successfully retried {$count} jobs",
            'data' => [
                'retried_count' => $count,
            ],
        ]);
    }

    /**
     * 删除单条失败记录
     */
    public function delete(Request $request): Response
    {
        $queue = (string) $request->post('queue', 'default');
        $index = (int) $request->post('index', 0);

        $success = $this->queueManager->deleteFailedJob($queue, $index);

        return json([
            'code' => $success ? 0 : 1,
            'msg' => $success ? 'Job deleted successfully' : 'Failed to delete job',
        ]);
    }
}
