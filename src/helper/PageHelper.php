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

namespace think\admin\helper;

use think\admin\Helper;
use think\db\Query;

/**
 * 列表处理管理器
 * Class PageHelper
 * @package think\admin\helper
 */
class PageHelper extends Helper
{
    /**
     * 是否启用分页
     * @var boolean
     */
    protected $page;

    /**
     * 集合分页记录数
     * @var integer
     */
    protected $total;

    /**
     * 集合每页记录数
     * @var integer
     */
    protected $limit;

    /**
     * 是否渲染模板
     * @var boolean
     */
    protected $display;

    /**
     * 逻辑器初始化
     * @param string|Query $dbQuery
     * @param boolean $page 是否启用分页
     * @param boolean $display 是否渲染模板
     * @param boolean $total 集合分页记录数
     * @param integer $limit 集合每页记录数
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function init($dbQuery, $page = true, $display = true, $total = false, $limit = 0)

    {
        $this->page = $page;
        $this->total = $total;
        $this->limit = $limit;
        $this->display = $display;
        $this->query = $this->buildQuery($dbQuery);
        // 数据列表排序处理
        if ($this->app->request->isPost()) {
            $post = $this->app->request->post();
            $sort = intval(isset($post['sort']) ? $post['sort'] : 0);
            unset($post['action'], $post['sort']);
            if ($this->app->db->table($this->query->getTable())->where($post)->update(['sort' => $sort]) !== false) {
                return $this->controller->success('列表排序修改成功！', '');
            } else {
                return $this->controller->error('列表排序修改失败，请稍候再试！');
            }
        }
        // 未配置 order 规则时自动按 sort 字段排序
        if (!$this->query->getOptions('order') && method_exists($this->query, 'getTableFields')) {
            if (in_array('sort', $this->query->getTableFields())) $this->query->order('sort desc');
        }
        // 列表分页及结果集处理
        if ($this->page) {
            // 分页每页显示记录数
            if ($this->limit > 0) {
                $limit = intval($this->limit);
            } else {
                $limit = $this->app->request->get('limit', $this->app->cookie->get('limit'));
                $this->app->cookie->set('limit', $limit = intval($limit >= 10 ? $limit : 20));
            }
            list($select, $query) = ['', $this->app->request->get()];
            $paginate = $this->query->paginate(['list_rows' => $limit, 'query' => $query], $this->total);
            foreach ([10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120, 130, 140, 150, 160, 170, 180, 190, 200] as $num) {
                list($query['limit'], $query['page'], $selected) = [$num, 1, $limit === $num ? 'selected' : ''];
                if (stripos($this->app->request->get('spm', '-'), 'm-') === 0) {
                    $url = url('@admin') . '#' . $this->app->request->baseUrl() . '?' . urldecode(http_build_query($query));
                } else {
                    $url = $this->app->request->baseUrl() . '?' . urldecode(http_build_query($query));
                }
                $select .= "<option data-num='{$num}' value='{$url}' {$selected}>{$num}</option>";
            }
            $pagehtml = "<div class='pagination-container nowrap'><span>共 {$paginate->total()} 条记录，每页显示 <select onchange='location.href=this.options[this.selectedIndex].value' data-auto-none>{$select}</select> 条，共 {$paginate->lastPage()} 页当前显示第 {$paginate->currentPage()} 页。</span>{$paginate->render()}</div>";
            if (stripos($this->app->request->get('spm', '-'), 'm-') === 0) {
                $this->controller->assign('pagehtml', preg_replace('|href="(.*?)"|', 'data-open="$1" onclick="return false" href="$1"', $pagehtml));
            } else {
                $this->controller->assign('pagehtml', $pagehtml);
            }
            $result = ['page' => ['limit' => intval($limit), 'total' => intval($paginate->total()), 'pages' => intval($paginate->lastPage()), 'current' => intval($paginate->currentPage())], 'list' => $paginate->items()];
        } else {
            $result = ['list' => $this->query->select()->toArray()];
        }
        if (false !== $this->controller->callback('_page_filter', $result['list']) && $this->display) {
            return $this->controller->fetch('', $result);
        } else {
            return $result;
        }
    }

}
