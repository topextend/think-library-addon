<?php
// -----------------------------------------------------------------------
// |Author       : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Date         : 2020-07-08 16:36:17
// |----------------------------------------------------------------------
// |LastEditTime : 2020-08-13 19:43:24
// |----------------------------------------------------------------------
// |LastEditors  : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Description  : Class Version
// |----------------------------------------------------------------------
// |FilePath     : \think-library\src\multiple\command\Clear.php
// |----------------------------------------------------------------------
// |Copyright (c) 2020 http://www.ladmin.cn   All rights reserved. 
// -----------------------------------------------------------------------
namespace think\admin\multiple\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class Clear extends Command
{
    protected function configure()
    {
        $this->setName('clear')->addArgument('app', Argument::OPTIONAL, 'app name .');
        $this->addOption('cache', 'c', Option::VALUE_NONE, 'clear cache file');
        $this->addOption('log', 'l', Option::VALUE_NONE, 'clear log file');
        $this->addOption('dir', 'r', Option::VALUE_NONE, 'clear empty dir');
        $this->setDescription('Clear runtime file');
    }

    protected function execute(Input $input, Output $output)
    {
        $app = $input->getArgument('app') ?: '';
        $runtimePath = $this->app->getRootPath() . 'runtime' . DIRECTORY_SEPARATOR . ($app ? $app . DIRECTORY_SEPARATOR : '');
        if ($input->getOption('cache')) {
            $path = $runtimePath . 'cache';
        } elseif ($input->getOption('log')) {
            $path = $runtimePath . 'log';
        } else {
            $path = $runtimePath;
        }
        $rmdir = $input->getOption('dir') ? true : false;
        $this->clear(rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR, $rmdir);
        $output->writeln("<info>Clear Successed</info>");
    }

    protected function clear(string $path, bool $rmdir): void
    {
        $files = is_dir($path) ? scandir($path) : [];
        foreach ($files as $file) {
            if ('.' != $file && '..' != $file && is_dir($path . $file)) {
                array_map('unlink', glob($path . $file . DIRECTORY_SEPARATOR . '*.*'));
                if ($rmdir) rmdir($path . $file);
            } elseif ('.gitignore' != $file && is_file($path . $file)) {
                unlink($path . $file);
            }
        }
    }
}
