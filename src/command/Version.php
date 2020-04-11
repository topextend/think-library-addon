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

use think\admin\service\ProcessService;
use think\App;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * 获取框架版本号
 * Class Version
 * @package think\admin\command
 */
class Version extends Command
{
    protected function configure()
    {
        $this->setName('xadmin:version');
        $this->setDescription("Query application framework version");
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln("ThinkAdmin " . ProcessService::instance()->version());
        $output->writeln('ThinkPHPCore ' . App::VERSION);
    }
}