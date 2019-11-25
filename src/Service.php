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

namespace think\admin;

use think\App;
use think\Container;

/**
 * 自定义服务接口
 * Class Service
 * @package think\admin
 */
abstract class Service
{
    /**
     * 应用实例
     * @var App
     */
    protected $app;

    /**
     * Service constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 服务初始化
     * @return static
     */
    public function initialize(): Service
    {
        return $this;
    }

    /**
     * 静态实例对象
     * @return static
     */
    public static function instance(): Service
    {
        return Container::getInstance()->make(static::class)->initialize();
    }
}