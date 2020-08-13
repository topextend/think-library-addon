<?php
// -----------------------------------------------------------------------
// |Author       : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Date         : 2020-08-13 11:41:27
// |----------------------------------------------------------------------
// |LastEditTime : 2020-08-13 19:46:51
// |----------------------------------------------------------------------
// |LastEditors  : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Description  : Config For Addons
// |----------------------------------------------------------------------
// |FilePath     : \think-library\src\Config.php
// |----------------------------------------------------------------------
// |Copyright (c) 2020 http://www.ladmin.cn   All rights reserved. 
// -----------------------------------------------------------------------
return [
    // 是否自动读取取插件钩子配置信息（默认是开启）
    'autoload' => false,
    // 数据表获取钩子
    'database'   => [
        // 查询缓存时间（单位秒，0为不缓存）
        'expire' => 0,
        // 钩子数据缓存标识
        'cache'  => '__hooks_data_cache__',
        // 钩子数据存放表名称
        'table'  => 'hooks',
        //钩子数据读取字段 （mark = 钩子标识，list = 使用钩子的插件列表)
        'field'  => [ 'mark', 'list' ]
    ],
    // 当关闭自动获取配置时需要手动配置hooks信息
    'hooks'      => [],
    'route'      => [],
    'service'    => [],
    // 自定义插件文件夹名
    'dir'        => 'addons',
    'path'       => app()->getRootPath() . 'addons/',
    'middleware' => []
];