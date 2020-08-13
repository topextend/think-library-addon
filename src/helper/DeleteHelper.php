<?php
// -----------------------------------------------------------------------
// |Author       : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Date         : 2020-07-08 16:36:17
// |----------------------------------------------------------------------
// |LastEditTime : 2020-08-13 19:42:00
// |----------------------------------------------------------------------
// |LastEditors  : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Description  : Class DeleteHepler
// |----------------------------------------------------------------------
// |FilePath     : \think-library\src\helper\DeleteHelper.php
// |----------------------------------------------------------------------
// |Copyright (c) 2020 http://www.ladmin.cn   All rights reserved. 
// -----------------------------------------------------------------------
namespace think\admin\helper;

use think\admin\Helper;
use think\db\Query;

/**
 * 通用删除管理器
 * Class DeleteHelper
 * @package think\admin\helper
 */
class DeleteHelper extends Helper
{
    /**
     * 数据对象主键名称
     * @var string
     */
    protected $field;

    /**
     * 数据对象主键值
     * @var string
     */
    protected $value;

    /**
     * 逻辑器初始化
     * @param string|Query $dbQuery
     * @param string $field 操作数据主键
     * @param array $where 额外更新条件
     * @return boolean|null|void
     * @throws \think\db\exception\DbException
     */
    public function init($dbQuery, $field = '', $where = [])
    {
        $this->query = $this->buildQuery($dbQuery);
        $this->field = $field ?: $this->query->getPk();
        $this->value = $this->app->request->post($this->field, null);
        // 查询限制处理
        if (!empty($where)) $this->query->where($where);
        if (!isset($where[$this->field]) && is_string($this->value)) {
            $this->query->whereIn($this->field, explode(',', $this->value));
        }
        // 前置回调处理
        if (false === $this->controller->callback('_delete_filter', $this->query, $where)) {
            return null;
        }
        // 阻止危险操作
        if (!$this->query->getOptions('where')) {
            $this->controller->error(lang('think_library_delete_error'));
        }
        // 组装执行数据
        $data = [];
        if (method_exists($this->query, 'getTableFields')) {
            $fields = $this->query->getTableFields();
            if (in_array('deleted', $fields)) $data['deleted'] = 1;
            if (in_array('is_deleted', $fields)) $data['is_deleted'] = 1;
        }
        // 执行删除操作
        $result = empty($data) ? $this->query->delete() : $this->query->update($data);
        // 结果回调处理
        if (false === $this->controller->callback('_delete_result', $result)) {
            return $result;
        }
        // 回复返回结果
        if ($result !== false) {
            $this->controller->success(lang('think_library_delete_success'), '');
        } else {
            $this->controller->error(lang('think_library_delete_error'));
        }
    }
}
