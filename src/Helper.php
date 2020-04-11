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

namespace think\admin;

use think\App;
use think\Container;
use think\db\Query;

/**
 * 控制器挂件
 * Class Helper
 * @package think\admin
 */
abstract class Helper
{
    /**
     * 当前应用容器
     * @var App
     */
    public $app;

    /**
     * 数据库实例
     * @var Query
     */
    public $query;

    /**
     * 当前控制器实例
     * @var Controller
     */
    public $controller;

    /**
     * Helper constructor.
     * @param App $app
     * @param Controller $controller
     */
    public function __construct(Controller $controller, App $app)
    {
        $this->app = $app;
        $this->controller = $controller;
    }

    /**
     * 获取数据库对象
     * @param string|Query $dbQuery
     * @return Query
     */
    protected function buildQuery($dbQuery): Query
    {
        return is_string($dbQuery) ? $this->app->db->name($dbQuery) : $dbQuery;
    }

    /**
     * 实例对象反射
     * @param array $args
     * @return static
     */
    public static function instance(...$args): Helper
    {
        return Container::getInstance()->invokeClass(static::class, $args);
    }
}