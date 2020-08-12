<?php
// -----------------------------------------------------------------------
// |Author       : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Date         : 2020-07-08 16:36:17
// |----------------------------------------------------------------------
// |LastEditTime : 2020-07-08 17:27:06
// |----------------------------------------------------------------------
// |LastEditors  : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Description  : Class NodeService
// |----------------------------------------------------------------------
// |FilePath     : \think-library\src\service\NodeService.php
// |----------------------------------------------------------------------
// |Copyright (c) 2020 http://www.ladmin.cn   All rights reserved. 
// -----------------------------------------------------------------------
namespace think\admin\service;

use think\admin\Service;

/**
 * 应用节点服务管理
 * Class NodeService
 * @package think\admin\service
 */
class NodeService extends Service
{
    /**
     * 驼峰转下划线规则
     * @param string $name
     * @return string
     */
    public function nameTolower($name)
    {
        $dots = [];
        foreach (explode('.', strtr($name, '/', '.')) as $dot) {
            $dots[] = trim(preg_replace("/[A-Z]/", "_\\0", $dot), "_");
        }
        return strtolower(join('.', $dots));
    }

    /**
     * 获取当前节点内容
     * @param string $type
     * @return string
     */
    public function getCurrent($type = '')
    {
        $prefix = $this->app->getNamespace();
        $middle = '\\' . $this->nameTolower($this->app->request->controller());
        $suffix = ($type === 'controller') ? '' : ('\\' . $this->app->request->action());
        return strtr(substr($prefix, stripos($prefix, '\\') + 1) . $middle . $suffix, '\\', '/');
    }

    /**
     * 检查并完整节点内容
     * @param string $node
     * @return string
     */
    public function fullnode($node)
    {
        if (empty($node)) return $this->getCurrent();
        if (count($attrs = explode('/', $node)) === 1) {
            return $this->getCurrent('controller') . "/{$node}";
        } else {
            $attrs[1] = $this->nameTolower($attrs[1]);
            return join('/', $attrs);
        }
    }

    /**
     * 获取应用列表
     * @param array $data
     * @return array
     */
    public function getModules($data = [])
    {
        if ($handle = opendir($this->app->getBasePath())) {
            while (false !== ($file = readdir($handle))) if ($file !== "." && $file !== "..") {
                if (is_dir($this->app->getBasePath() . $file)) $data[] = $file;
            }
            closedir($handle);
        }
        return $data;
    }

    /**
     * 获取所有控制器入口
     * @param boolean $force
     * @return array
     * @throws \ReflectionException
     */
    public function getMethods($force = false)
    {
        static $data = [];
        if (empty($force)) {
            if (count($data) > 0) return $data;
            $data = $this->app->cache->get('SystemAuthNode', []);
            if (count($data) > 0) return $data;
        } else {
            $data = [];
        }
        $ignores = get_class_methods('\think\admin\Controller');
        if(!is_dir($this->app->addons->getAddonsPath())){
            $path = $this->_scanDirectory($this->app->getBasePath());
        } else {
            $path = array_merge($this->_scanDirectory($this->app->getBasePath()),$this->_scanDirectory($this->app->addons->getAddonsPath()));
        }
        foreach ($path as $file) {
            if (preg_match("|/(\w+)/(\w+)/controller/(.+)\.php$|i", $file, $matches)) {
                list(, $namespace, $appname, $classname) = $matches;
                $class = new \ReflectionClass(strtr("{$namespace}/{$appname}/controller/{$classname}", '/', '\\'));
                if (strpos($file,'addons') === false)
                {
                    $prefix = strtr("{$appname}/{$this->nameTolower($classname)}", '\\', '/');
                }
                else{
                    $prefix = strtr("addons/{$appname}/{$this->nameTolower($classname)}", '\\', '/');
                }
                $data[$prefix] = $this->_parseComment($class->getDocComment(), $classname);
                foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                    if (in_array($metname = $method->getName(), $ignores)) continue;
                    $data["{$prefix}/{$metname}"] = $this->_parseComment($method->getDocComment(), $metname);
                }
            }
        }
        $data = array_change_key_case($data, CASE_LOWER);
        $this->app->cache->set('SystemAuthNode', $data);
        return $data;
    }

    /**
     * 解析硬节点属性
     * @param string $comment 备注内容
     * @param string $default 默认标题
     * @return array
     */
    private function _parseComment($comment, $default = '')
    {
        $text = strtr($comment, "\n", ' ');
        $title = preg_replace('/^\/\*\s*\*\s*\*\s*(.*?)\s*\*.*?$/', '$1', $text);
        foreach (['@auth', '@menu', '@login'] as $find) if (stripos($title, $find) === 0) {
            $title = $default;
        }
        return [
            'title'   => $title ?: $default,
            'isauth'  => intval(preg_match('/@auth\s*true/i', $text)),
            'ismenu'  => intval(preg_match('/@menu\s*true/i', $text)),
            'islogin' => intval(preg_match('/@login\s*true/i', $text)),
        ];
    }

    /**
     * 获取所有PHP文件列表
     * @param string $path 扫描目录
     * @param array $data 额外数据
     * @param string $ext 有文件后缀
     * @return array
     */
    private function _scanDirectory($path, $data = [], $ext = 'php')
    {
        foreach (glob("{$path}*") as $item) {
            if (is_dir($item)) {
                $data = array_merge($data, $this->_scanDirectory("{$item}" . DIRECTORY_SEPARATOR));
            } elseif (is_file($item) && pathinfo($item, PATHINFO_EXTENSION) === $ext) {
                $data[] = strtr($item, '\\', '/');
            }
        }
        return $data;
    }
}