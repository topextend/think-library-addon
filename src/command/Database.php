<?php
// -----------------------------------------------------------------------
// |Author       : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Date         : 2020-07-08 16:36:17
// |----------------------------------------------------------------------
// |LastEditTime : 2020-08-13 19:40:34
// |----------------------------------------------------------------------
// |LastEditors  : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Description  : Class Database
// |----------------------------------------------------------------------
// |FilePath     : \think-library\src\command\Database.php
// |----------------------------------------------------------------------
// |Copyright (c) 2020 http://www.ladmin.cn   All rights reserved. 
// -----------------------------------------------------------------------
namespace think\admin\command;

use think\admin\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

/**
 * 数据库修复优化指令
 * Class Database
 * @package think\admin\command
 */
class Database extends Command
{
    public function configure()
    {
        $this->setName('xadmin:database');
        $this->addArgument('action', Argument::OPTIONAL, 'repair|optimize', 'optimize');
        $this->setDescription('Database Optimize and Repair for ThinkAdmin');
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return mixed
     */
    public function execute(Input $input, Output $output)
    {
        $do = $input->getArgument('action');
        if (in_array($do, ['repair', 'optimize'])) return $this->{"_{$do}"}();
        $this->output->error("Wrong operation, currently allow repair|optimize");
    }

    /**
     * 修复数据表
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function _repair()
    {
        $this->setQueueProgress("正在获取需要修复的数据表", 0);
        [$total, $used] = [count($tables = $this->getTables()), 0];
        $this->setQueueProgress("总共需要修复 {$total} 张数据表", 0);
        foreach ($tables as $table) {
            $stridx = str_pad(++$used, strlen("{$total}"), '0', STR_PAD_LEFT) . "/{$total}";
            $this->setQueueProgress("[{$stridx}] 正在修复数据表 {$table}", $used / $total * 100);
            $this->app->db->query("REPAIR TABLE `{$table}`");
        }
    }

    /**
     * 优化所有数据表
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function _optimize()
    {
        $this->setQueueProgress("正在获取需要优化的数据表", 0);
        [$total, $used] = [count($tables = $this->getTables()), 0];
        $this->setQueueProgress("总共需要优化 {$total} 张数据表", 0);
        foreach ($tables as $table) {
            $stridx = str_pad(++$used, strlen("{$total}"), '0', STR_PAD_LEFT) . "/{$total}";
            $this->setQueueProgress("[{$stridx}] 正在优化数据表 {$table}", $used / $total * 100);
            $this->app->db->query("OPTIMIZE TABLE `{$table}`");
        }
    }

    /**
     * 获取数据库的数据表
     * @return array
     */
    protected function getTables()
    {
        $tables = [];
        foreach ($this->app->db->query("show tables") as $item) {
            $tables = array_merge($tables, array_values($item));
        }
        return $tables;
    }

}