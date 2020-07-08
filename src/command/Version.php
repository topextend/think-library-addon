<?php
// -----------------------------------------------------------------------
// |Author       : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Date         : 2020-07-08 16:36:17
// |----------------------------------------------------------------------
// |LastEditTime : 2020-07-08 17:19:51
// |----------------------------------------------------------------------
// |LastEditors  : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Description  : Class Version
// |----------------------------------------------------------------------
// |FilePath     : \think-library\src\command\Version.php
// |----------------------------------------------------------------------
// |Copyright (c) 2020 http://www.ladmin.cn   All rights reserved. 
// -----------------------------------------------------------------------
namespace think\admin\command;

use think\admin\Command;
use think\console\Input;
use think\console\Output;

/**
 * 框架版本号指令
 * Class Version
 * @package think\admin\command
 */
class Version extends Command
{
    protected function configure()
    {
        $this->setName('xadmin:version');
        $this->setDescription("ThinkLibrary and ThinkPHP Version for ThinkAdmin");
    }

    /**
     * @param Input $input
     * @param Output $output
     */
    protected function execute(Input $input, Output $output)
    {
        $output->writeln('ThinkLib ' . $this->process->version());
        $output->writeln('ThinkPHP ' . $this->app->version());
    }
}