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
     * @var array|string
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
        $this->field = empty($field) ? ($this->query->getPk() ? $this->query->getPk() : 'id') : $field;;
        $this->value = input($this->field, isset($data[$this->field]) ? $data[$this->field] : null);
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
                    if ($result !== false) $this->controller->success('恭喜, 数据保存成功!', '');
                    $this->controller->error('数据保存失败, 请稍候再试!');
                }
                return $result;
            }
        }
    }

}
