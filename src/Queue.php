<?php
// -----------------------------------------------------------------------
// |Author       : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Date         : 2020-07-08 16:36:17
// |----------------------------------------------------------------------
// |LastEditTime : 2020-07-08 17:31:00
// |----------------------------------------------------------------------
// |LastEditors  : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Description  : Class Queue
// |----------------------------------------------------------------------
// |FilePath     : \think-library\src\Queue.php
// |----------------------------------------------------------------------
// |Copyright (c) 2020 http://www.ladmin.cn   All rights reserved. 
// -----------------------------------------------------------------------
namespace think\admin;

use think\admin\service\ProcessService;
use think\admin\service\QueueService;
use think\App;

/**
 * 任务基础类
 * Class Queue
 * @package think\admin
 */
abstract class Queue
{
    /**
     * 应用实例
     * @var App
     */
    protected $app;

    /**
     * 任务控制服务
     * @var QueueService
     */
    protected $queue;

    /**
     * 进程控制服务
     * @var ProcessService
     */
    protected $process;

    /**
     * Queue constructor.
     * @param App $app
     * @param ProcessService $process
     */
    public function __construct(App $app, ProcessService $process)
    {
        $this->app = $app;
        $this->process = $process;
    }

    /**
     * 初始化任务数据
     * @param QueueService $queue
     * @return $this
     */
    public function initialize(QueueService $queue)
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * 执行任务处理内容
     * @param array $data
     * @return mixed
     */
    abstract public function execute($data = []);

    /**
     * 设置任务的进度
     * @param null|string $message 进度消息
     * @param null|integer $progress 进度数值
     * @return Queue
     */
    protected function setQueueProgress($message = null, $progress = null)
    {
        $this->queue->progress(2, $message, $progress);
        return $this;
    }

    /**
     * 设置成功的消息
     * @param string $message 消息内容
     * @throws Exception
     */
    protected function setQueueSuccess($message)
    {
        throw new Exception($message, 3, $this->queue->code);
    }

    /**
     * 设置失败的消息
     * @param string $message 消息内容
     * @throws Exception
     */
    protected function setQueueError($message)
    {
        throw new Exception($message, 4, $this->queue->code);
    }
}