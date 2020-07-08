<?php
// -----------------------------------------------------------------------
// |Author       : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Date         : 2020-07-08 16:36:17
// |----------------------------------------------------------------------
// |LastEditTime : 2020-07-08 17:23:32
// |----------------------------------------------------------------------
// |LastEditors  : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Description  : Class TokenHelper
// |----------------------------------------------------------------------
// |FilePath     : \think-library\src\helper\TokenHelper.php
// |----------------------------------------------------------------------
// |Copyright (c) 2020 http://www.ladmin.cn   All rights reserved. 
// -----------------------------------------------------------------------
namespace think\admin\helper;

use think\admin\Helper;
use think\admin\service\TokenService;
use think\exception\HttpResponseException;

/**
 * 表单令牌验证器
 * Class TokenHelper
 * @package think\admin\helper
 */
class TokenHelper extends Helper
{

    /**
     * 初始化验证码器
     * @param boolean $return
     * @return boolean
     */
    public function init($return = false)
    {
        $this->controller->csrf_state = true;
        if ($this->app->request->isPost() && !TokenService::instance()->checkFormToken()) {
            if ($return) return false;
            $this->controller->error($this->controller->csrf_message);
        } else {
            return true;
        }
    }

    /**
     * 清理表单令牌
     */
    public function clear()
    {
        TokenService::instance()->clearFormToken();
    }

    /**
     * 返回视图内容
     * @param string $tpl 模板名称
     * @param array $vars 模板变量
     * @param string $node CSRF授权节点
     */
    public function fetchTemplate($tpl = '', $vars = [], $node = null)
    {
        throw new HttpResponseException(view($tpl, $vars, 200, function ($html) use ($node) {
            return preg_replace_callback('/<\/form>/i', function () use ($node) {
                $csrf = TokenService::instance()->buildFormToken($node);
                return "<input type='hidden' name='_token_' value='{$csrf['token']}'></form>";
            }, $html);
        }));
    }

}