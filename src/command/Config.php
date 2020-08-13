<?php
// -----------------------------------------------------------------------
// |Author       : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Date         : 2020-08-13 11:28:28
// |----------------------------------------------------------------------
// |LastEditTime : 2020-08-13 19:40:26
// |----------------------------------------------------------------------
// |LastEditors  : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Description  : Config For Addons Class
// |----------------------------------------------------------------------
// |FilePath     : \think-library\src\command\Config.php
// |----------------------------------------------------------------------
// |Copyright (c) 2020 http://www.ladmin.cn   All rights reserved. 
// -----------------------------------------------------------------------
namespace think\admin\command;

use think\admin\Command;
use think\console\Input;
use think\console\Output;

class Config extends Command
{
    public function configure()
    {
        $this->setName('addons:config')->setDescription('send config to config folder');
    }

    public function execute(Input $input, Output $output)
    {
        //获取默认配置文件
        $content = file_get_contents(__DIR__ . '\config.php');

        $configPath = config_path() . '/';
        $configFile = $configPath . 'addons.php';

        //判断目录是否存在
        if (!file_exists($configPath)) {
            mkdir($configPath, 0755, true);
        }

        //判断文件是否存在
        if (is_file($configFile)) {
            throw new \InvalidArgumentException(sprintf('The config file "%s" already exists', $configFile));
        }

        if (false === file_put_contents($configFile, $content)) {
            throw new \RuntimeException(sprintf('The config file "%s" could not be written to "%s"', $configFile,$configPath));
        }

        $output->writeln('create addons config ok');
    }
}
