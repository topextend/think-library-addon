<?php
// -----------------------------------------------------------------------
// |Author       : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Date         : 2020-07-08 16:36:17
// |----------------------------------------------------------------------
// |LastEditTime : 2020-07-08 17:23:44
// |----------------------------------------------------------------------
// |LastEditors  : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Description  : Class ValidateHelper
// |----------------------------------------------------------------------
// |FilePath     : \think-library\src\helper\ValidateHelper.php
// |----------------------------------------------------------------------
// |Copyright (c) 2020 http://www.ladmin.cn   All rights reserved. 
// -----------------------------------------------------------------------
namespace think\admin\helper;

use think\admin\Helper;
use think\Validate;

/**
 * 快捷输入验证器
 * Class ValidateHelper
 * @package think\admin\helper
 */
class ValidateHelper extends Helper
{
    /**
     * 快捷输入并验证（ 支持 规则 # 别名 ）
     * @param array $rules 验证规则（ 验证信息数组 ）
     * @param string $type 输入方式 ( post. 或 get. )
     * @return array
     *  age.max:100 => message // 最大值限定
     *  name.require => message // 必填内容
     *  name.between:1,120 => message // 范围限定
     *  name.value => value // 固定字段数值内容
     *  name.default => 100 // 获取并设置默认值
     *  更多规则参照 ThinkPHP 官方的验证类
     */
    public function init(array $rules, $type = '')
    {
        list($data, $rule, $info, $alias) = [[], [], [], ''];
        foreach ($rules as $name => $message) {
            if (stripos($name, '#') !== false) {
                list($name, $alias) = explode('#', $name);
            }
            if (stripos($name, '.') === false) {
                if (is_numeric($name)) {
                    $field = $message;
                    if (is_string($message) && stripos($message, '#') !== false) {
                        list($name, $alias) = explode('#', $message);
                        $field = empty($alias) ? $name : $alias;
                    }
                    $data[$name] = input("{$type}{$field}");
                } else {
                    $data[$name] = $message;
                }
            } else {
                list($_rgx) = explode(':', $name);
                list($_key, $_rule) = explode('.', $name);
                if (in_array($_rule, ['value', 'default'])) {
                    if ($_rule === 'value') {
                        $data[$_key] = $message;
                    } elseif ($_rule === 'default') {
                        $data[$_key] = input($type . ($alias ?: $_key), $message);
                    }
                } else {
                    $info[$_rgx] = $message;
                    $data[$_key] = $data[$_key] ?? input($type . ($alias ?: $_key));
                    $rule[$_key] = empty($rule[$_key]) ? $_rule : "{$rule[$_key]}|{$_rule}";
                }
            }
        }
        $validate = new Validate();
        if ($validate->rule($rule)->message($info)->check($data)) {
            return $data;
        } else {
            $this->controller->error($validate->getError());
        }
    }
}