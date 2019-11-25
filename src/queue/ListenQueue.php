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
 * 启动监听任务的主进程
 * Class ListenQueue
 * @package think\admin\queue
 */
class ListenQueue extends Command
{
    /**
     * 配置指定信息
     */
    protected function configure()
    {
        $this->setName('xtask:listen')->setDescription('[监听]启动任务监听主进程');
    }

    /**
     * 执行进程守护监听
     * @param Input $input
     * @param Output $output
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function execute(Input $input, Output $output)
    {
        $this->app->db->name('Queue')->count();
        if (($process = ProcessService::instance())->iswin()) {
            $this->setProcessTitle("ThinkAdmin 监听主进程 {$process->version()}");
        }
        $output->comment('============ 任务监听中 ============');
        while (true) {
            foreach ($this->app->db->name('Queue')->where([['status', '=', '1'], ['exec_time', '<=', time()]])->order('exec_time asc')->limit(100)->select() as $vo) {
                try {
                    $this->app->db->name('Queue')->where(['code' => $vo['code']])->update(['status' => '2', 'enter_time' => time(), 'exec_desc' => '', 'attempts' => $vo['attempts'] + 1]);
                    if ($process->query($command = $process->think("xtask:_work {$vo['code']} -"))) {
                        $output->comment("正在执行 -> [{$vo['code']}] {$vo['title']}");
                    } else {
                        $process->create($command);
                        $output->info("创建成功 -> [{$vo['code']}] {$vo['title']}");
                    }
                } catch (\Exception $e) {
                    $this->app->db->name('Queue')->where(['code' => $vo['code']])->update(['status' => '4', 'outer_time' => time(), 'exec_desc' => $e->getMessage()]);
                    $output->error("创建失败 -> [{$vo['code']}] {$vo['title']}，{$e->getMessage()}");
                }
            }
            sleep(1);
        }
    }

}
