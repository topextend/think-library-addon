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

use think\admin\Service;

/**
 * 表单令牌管理服务
 * Class TokenService
 * @package think\admin\service
 */
class TokenService extends Service
{
    /**
     * 获取当前请求令牌
     * @return array|string
     */
    public function getInputToken()
    {
        return $this->app->request->header('user-form-token', input('_csrf_', ''));
    }

    /**
     * 验证表单令牌是否有效
     * @param string $token 表单令牌
     * @param string $node 授权节点
     * @return boolean
     */
    public function checkFormToken($token = null, $node = null)
    {
        if (is_null($token)) $token = $this->getInputToken();
        if (is_null($node)) $node = NodeService::instance()->getCurrent();
        // 读取缓存并检查是否有效
        $cache = $this->app->session->get($token, []);
        if (empty($cache['node']) || empty($cache['time']) || empty($cache['token'])) return false;
        if ($cache['time'] + 600 < time() || strtolower($cache['node']) !== strtolower($node)) return false;
        return true;
    }

    /**
     * 清理表单CSRF信息
     * @param string $token
     * @return $this
     */
    public function clearFormToken($token = null)
    {
        if (is_null($token)) $token = $this->getInputToken();
        $this->app->session->delete($token);
        return $this;
    }

    /**
     * 生成表单CSRF信息
     * @param null|string $node
     * @return array
     */
    public function buildFormToken($node = null)
    {
        list($token, $time) = [uniqid('csrf') . rand(1000, 9999), time()];
        foreach ($this->app->session->all() as $key => $item) {
            if (stripos($key, 'csrf') === 0 && isset($item['time'])) {
                if ($item['time'] + 600 < $time) $this->clearFormToken($key);
            }
        }
        $data = ['node' => NodeService::instance()->fullnode($node), 'token' => $token, 'time' => $time];
        $this->app->session->set($token, $data);
        return $data;
    }
}