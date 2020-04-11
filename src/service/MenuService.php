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

namespace think\admin\service;

use think\admin\extend\DataExtend;
use think\admin\Service;

/**
 * 系统菜单管理服务
 * Class MenuService
 * @package app\admin\service
 */
class MenuService extends Service
{

    /**
     * 获取可选菜单节点
     * @return array
     * @throws \ReflectionException
     */
    public function getList()
    {
        static $nodes = [];
        if (count($nodes) > 0) return $nodes;
        foreach (NodeService::instance()->getMethods() as $node => $method) {
            if ($method['ismenu']) $nodes[] = ['node' => $node, 'title' => $method['title']];
        }
        return $nodes;
    }

    /**
     * 获取系统菜单树数据
     * @return array
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getTree()
    {
        $result = $this->app->db->name('SystemMenu')->where(['status' => '1'])->order('sort desc,id asc')->select();
        return $this->buildData(DataExtend::arr2tree($result->toArray()), NodeService::instance()->getMethods());
    }

    /**
     * 后台主菜单权限过滤
     * @param array $menus 当前菜单列表
     * @param array $nodes 系统权限节点
     * @return array
     * @throws \ReflectionException
     */
    private function buildData($menus, $nodes)
    {
        foreach ($menus as $key => &$menu) {
            if (!empty($menu['sub'])) {
                $menu['sub'] = $this->buildData($menu['sub'], $nodes);
            }
            if (!empty($menu['sub'])) $menu['url'] = '#';
            elseif ($menu['url'] === '#') unset($menus[$key]);
            elseif (preg_match('|^https?://|i', $menu['url'])) continue;
            else {
                $node = join('/', array_slice(explode('/', preg_replace('/[\W]/', '/', $menu['url'])), 0, 3));
                $menu['url'] = url($menu['url']) . (empty($menu['params']) ? '' : "?{$menu['params']}");
                if (!AdminService::instance()->check($node)) unset($menus[$key]);
            }
        }
        return $menus;
    }
}