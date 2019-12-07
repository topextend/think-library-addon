<?php

// +----------------------------------------------------------------------
// | Think-Library
// +----------------------------------------------------------------------
// | 官方网站: http://www.ladmin.cn
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://github.com/topextend/think-library
// +----------------------------------------------------------------------

namespace think\admin\queue;

use think\admin\service\ProcessService;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 查看任务监听的主进程状态
 * Class StateQueue
 * @package think\admin\queue
 */
class StateQueue extends Command
{
    /**
     * 指令属性配置
     */
    protected function configure()
    {
        $this->setName('xtask:state')->setDescription('[控制]查看监听主进程状态');
    }

    /**
     * 指令执行状态
     * @param Input $input
     * @param Output $output
     */
    protected function execute(Input $input, Output $output)
    {
        $service = ProcessService::instance();
        $command = $service->think('xtask:listen');
        if (count($result = $service->query($command)) > 0) {
            $output->info("任务监听主进程{$result[0]['pid']}正在运行...");
        } else {
            $output->warning("任务监听主进程没有运行哦!");
        }
    }
}
