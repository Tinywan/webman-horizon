<?php

/**
 * @desc 队列列表管理控制器
 * @author Tinywan(ShaoBo Wan)
 * @date 2026/09/14
 */

declare(strict_types=1);

namespace plugin\horizon\app\controller;

use plugin\horizon\app\service\QueueManager;
use support\Request;
use support\Response;

class QueueController
{
    protected QueueManager $queueManager;

    public function __construct()
    {
        $this->queueManager = new QueueManager();
    }

    /**
     * 获取队列详情列表
     */
    public function index(Request $request): Response
    {
        $queues = $this->queueManager->getAllQueueDetails();

        return json([
            'code' => 0,
            'msg' => 'ok',
            'data' => $queues,
        ]);
    }

    /**
     * 清空指定队列
     */
    public function clear(Request $request): Response
    {
        $queue = (string) $request->post('queue', '');
        $type = (string) $request->post('type', 'waiting');

        if (empty($queue)) {
            return json(['code' => 1, 'msg' => 'Queue name cannot be empty']);
        }

        $success = $this->queueManager->clearQueue($queue, $type);

        return json([
            'code' => $success ? 0 : 1,
            'msg' => $success ? 'Queue cleared successfully' : 'Failed to clear queue',
        ]);
    }
}
