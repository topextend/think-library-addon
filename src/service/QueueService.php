<?php

// +----------------------------------------------------------------------
// | Ladmin
// +----------------------------------------------------------------------
// | 官方网站: http://www.ladmin.cn
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://github.com/topextend/ladmin
// +----------------------------------------------------------------------

namespace think\admin\service;

use think\admin\extend\CodeExtend;
use think\admin\Service;

/**
 * 任务基础服务
 * Class QueueService
 * @package think\admin\service
 */
class QueueService extends Service
{

    /**
     * 当前任务编号
     * @var string
     */
    public $code = '';

    /**
     * 当前任务标题
     * @var string
     */
    public $title = '';

    /**
     * 当前任务参数
     * @var array
     */
    public $data = [];

    /**
     * 当前任务数据
     * @var array
     */
    public $queue = [];

    /**
     * 数据初始化
     * @param integer $code
     * @return static
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function initialize($code = 0)
    {
        if (!empty($code)) {
            $this->code = $code;
            $this->queue = $this->app->db->name('SystemQueue')->where(['code' => $this->code])->find();
            if (empty($this->queue)) {
                $this->app->log->error("Qeueu initialize failed, Queue {$code} not found.");
                throw new \think\admin\Exception("Qeueu initialize failed, Queue {$code} not found.");
            }
            $this->code = $this->queue['code'];
            $this->title = $this->queue['title'];
            $this->data = json_decode($this->queue['exec_data'], true) ?: [];
        }
        return $this;
    }

    /**
     * 判断是否WIN环境
     * @return boolean
     */
    protected function iswin()
    {
        return ProcessService::instance()->iswin();
    }

    /**
     * 重发异步任务
     * @param integer $wait 等待时间
     * @return $this
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function reset($wait = 0)
    {
        if (empty($this->queue)) {
            $this->app->log->error("Qeueu reset failed, Queue {$this->code} data cannot be empty!");
            throw new \think\admin\Exception("Qeueu reset failed, Queue {$this->code} data cannot be empty!");
        }
        $map = ['code' => $this->code];
        $this->app->db->name('SystemQueue')->where($map)->strict(false)->failException(true)->update([
            'exec_pid' => '0', 'exec_time' => time() + $wait, 'status' => '1',
        ]);
        return $this->initialize($this->code);
    }

    /**
     * 添加定时清理任务
     * @return $this
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addCleanQueue()
    {
        return $this->register('定时清理系统任务数据', "xtask:clean", 0, [], 0, 3600);
    }

    /**
     * 注册异步处理任务
     * @param string $title 任务名称
     * @param string $command 执行脚本
     * @param integer $later 延时时间
     * @param array $data 任务附加数据
     * @param integer $rscript 任务类型(0单例,1多例)
     * @param integer $loops 循环等待时间
     * @return $this
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function register($title, $command, $later = 0, $data = [], $rscript = 1, $loops = 0)
    {
        $map = [['title', '=', $title], ['status', 'in', ['1', '2']]];
        if (empty($rscript) && ($queue = $this->app->db->name('SystemQueue')->where($map)->find())) {
            throw new \think\admin\Exception(lang('think_library_queue_exist'), 0, $queue['code']);
        }
        $this->code = CodeExtend::uniqidDate(16, 'Q');
        $this->app->db->name('SystemQueue')->strict(false)->failException(true)->insert([
            'code'       => $this->code,
            'title'      => $title,
            'command'    => $command,
            'attempts'   => '0',
            'rscript'    => intval(boolval($rscript)),
            'exec_data'  => json_encode($data, JSON_UNESCAPED_UNICODE),
            'exec_time'  => $later > 0 ? time() + $later : time(),
            'enter_time' => '0',
            'outer_time' => '0',
            'loops_time' => $loops,
        ]);
        $this->progress(1, '>>> 任务创建成功 <<<', 0.00);
        return $this->initialize($this->code);
    }

    /**
     * 设置任务进度信息
     * @param null|integer $status 任务状态
     * @param null|string $message 进度消息
     * @param null|integer $progress 进度数值
     * @return array
     */
    public function progress($status = null, $message = null, $progress = null)
    {
        if (is_numeric($status) && intval($status) === 3) {
            if (!is_numeric($progress)) $progress = '100.00';
            if (is_null($message)) $message = '>>> 任务已经完成 <<<';
        }
        if (is_numeric($status) && intval($status) === 4) {
            if (!is_numeric($progress)) $progress = '0.00';
            if (is_null($message)) $message = '>>> 任务执行失败 <<<';
        }
        try {
            $data = $this->app->cache->get("queue_{$this->code}_progress", [
                'code' => $this->code, 'status' => $status, 'message' => $message, 'progress' => $progress, 'history' => [],
            ]);
        } catch (\Exception|\Error $exception) {
            return $this->progress($status, $message, $progress);
        }
        if (is_numeric($status)) $data['status'] = intval($status);
        if (is_numeric($progress)) $progress = str_pad(sprintf("%.2f", $progress), 6, "0", STR_PAD_LEFT);
        if (is_string($message) && is_null($progress)) {
            $data['message'] = $message;
            $data['history'][] = ['message' => $message, 'progress' => $data['progress'], 'datetime' => date('Y-m-d H:i:s')];
        } elseif (is_null($message) && is_numeric($progress)) {
            $data['progress'] = $progress;
            $data['history'][] = ['message' => $data['message'], 'progress' => $progress, 'datetime' => date('Y-m-d H:i:s')];
        } elseif (is_string($message) && is_numeric($progress)) {
            $data['message'] = $message;
            $data['progress'] = $progress;
            $data['history'][] = ['message' => $message, 'progress' => $progress, 'datetime' => date('Y-m-d H:i:s')];
        }
        if (is_string($message) || is_numeric($progress)) {
            if (count($data['history']) > 10) {
                $data['history'] = array_slice($data['history'], -10);
            }
            $this->app->cache->set("queue_{$this->code}_progress", $data);
        }
        return $data;
    }

    /**
     * 执行任务处理
     * @param array $data 任务参数
     * @return mixed
     */
    public function execute(array $data = [])
    {
    }

}