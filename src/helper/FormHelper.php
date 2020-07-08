<?php
// -----------------------------------------------------------------------
// |Author       : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Date         : 2020-07-08 16:36:17
// |----------------------------------------------------------------------
// |LastEditTime : 2020-07-08 17:23:01
// |----------------------------------------------------------------------
// |LastEditors  : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Description  : Class FormHelper
// |----------------------------------------------------------------------
// |FilePath     : \think-library\src\helper\FormHelper.php
// |----------------------------------------------------------------------
// |Copyright (c) 2020 http://www.ladmin.cn   All rights reserved. 
// -----------------------------------------------------------------------
namespace think\admin\helper;

use think\admin\Helper;
use think\db\Query;

/**
 * 表单视图管理器
 * Class FormHelper
 * @package think\admin\helper
 */
class FormHelper extends Helper
{
    /**
     * 表单扩展数据
     * @var array
     */
    protected $data;

    /**
     * 表单额外更新条件
     * @var array
     */
    protected $where;

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
     * 表单模板文件
     * @var string
     */
    protected $template;

    /**
     * 逻辑器初始化
     * @param string|Query $dbQuery
     * @param string $template 模板名称
     * @param string $field 指定数据主键
     * @param array $where 额外更新条件
     * @param array $data 表单扩展数据
     * @return array|boolean|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function init($dbQuery, $template = '', $field = '', $where = [], $data = [])
    {
        $this->query = $this->buildQuery($dbQuery);
        list($this->template, $this->where, $this->data) = [$template, $where, $data];
        $this->field = $field ?: ($this->query->getPk() ?: 'id');
        $this->value = input($this->field, $data[$this->field] ?? null);
        // GET请求, 获取数据并显示表单页面
        if ($this->app->request->isGet()) {
            if ($this->value !== null) {
                $where = [$this->field => $this->value];
                $data = (array)$this->query->where($where)->where($this->where)->find();
            }
            $data = array_merge($data, $this->data);
            if (false !== $this->controller->callback('_form_filter', $data)) {
                return $this->controller->fetch($this->template, ['vo' => $data]);
            }
            return $data;
        }
        // POST请求, 数据自动存库处理
        if ($this->app->request->isPost()) {
            $data = array_merge($this->app->request->post(), $this->data);
            if (false !== $this->controller->callback('_form_filter', $data, $this->where)) {
                $result = data_save($this->query, $data, $this->field, $this->where);
                if (false !== $this->controller->callback('_form_result', $result, $data)) {
                    if ($result !== false) {
                        $this->controller->success(lang('think_library_form_success'));
                    } else {
                        $this->controller->error(lang('think_library_form_error'));
                    }
                }
                return $result;
            }
        }
    }

}
