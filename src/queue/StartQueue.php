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
 * 检查并创建监听主进程
 * Class StartQueue
 * @package think\admin\queue
 */
class StartQueue extends Command
{

    /**
     * 指令属性配置
     */
    protected function configure()
    {
        $this->setName('xtask:start')->setDescription('[控制]创建守护监听主进程');
    }

    /**
     * 执行启动操作
     * @param Input $input
     * @param Output $output
     */
    protected function execute(Input $input, Output $output)
    {
        $this->app->db->name('Queue')->count();
        $process = ProcessService::instance();
        $command = $process->think("xtask:listen");
        if (count($result = $process->query($command)) > 0) {
            $output->info("监听主进程{$result['0']['pid']}已经启动！");
        } else {
            $process->create($command);
            sleep(1);
            if (count($result = $process->query($command)) > 0) {
                $output->info("监听主进程{$result['0']['pid']}启动成功！");
            } else {
                $output->error('监听主进程创建失败！');
            }
        }
    }
}
