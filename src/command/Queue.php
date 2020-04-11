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

namespace think\admin\command;

use think\admin\Command;
use think\admin\service\ProcessService;
use think\console\Input;
use think\console\Output;

/**
 * 系统任务基类
 * Class Queue
 * @package think\admin\command
 */
abstract class Queue extends Command
{
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'SystemQueue';

    /**
     * 进程服务对象
     * @var ProcessService
     */
    protected $process;

    /**
     * 任务指令初始化
     * @param Input $input
     * @param Output $output
     */
    public function initialize(Input $input, Output $output)
    {
        $this->process = ProcessService::instance();
    }
}