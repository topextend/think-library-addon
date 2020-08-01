<?php
// -----------------------------------------------------------------------
// |Author       : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Date         : 2020-07-08 16:36:17
// |----------------------------------------------------------------------
// |LastEditTime : 2020-07-08 17:29:15
// |----------------------------------------------------------------------
// |LastEditors  : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Description  : Class LocalStorage
// |----------------------------------------------------------------------
// |FilePath     : \think-library\src\storage\LocalStorage.php
// |----------------------------------------------------------------------
// |Copyright (c) 2020 http://www.ladmin.cn   All rights reserved. 
// -----------------------------------------------------------------------
namespace think\admin\storage;

use think\admin\Storage;

/**
 * 本地存储支持
 * Class LocalStorage
 * @package think\admin\storage
 */
class LocalStorage extends Storage
{

    /**
     * 初始化入口
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function initialize()
    {
        $type = strtolower(sysconf('storage.local_http_protocol'));
        if ($type === 'follow') $type = $this->app->request->scheme();
        $this->prefix = trim(dirname($this->app->request->baseFile(false)), '\\/');
        if ($type !== 'path') {
            $domain = sysconf('storage.local_http_domain') ?: $this->app->request->host();
            if ($type === 'auto') $this->prefix = "//{$domain}";
            elseif ($type === 'http') $this->prefix = "http://{$domain}";
            elseif ($type === 'https') $this->prefix = "https://{$domain}";
        }
    }

    /**
     * 获取当前实例对象
     * @param null $name
     * @return static
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function instance($name = null)
    {
        return parent::instance('local');
    }

    /**
     * 文件储存在本地
     * @param string $name 文件名称
     * @param string $file 文件内容
     * @param boolean $safe 安全模式
     * @param string $attname 下载名称
     * @return array
     */
    public function set($name, $file, $safe = false, $attname = null)
    {
        try {
            $path = $this->path($name, $safe);
            file_exists(dirname($path)) || mkdir(dirname($path), 0755, true);
            if (file_put_contents($path, $file)) {
                return $this->info($name, $safe, $attname);
            }
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 根据文件名读取文件内容
     * @param string $name 文件名称
     * @param boolean $safe 安全模式
     * @return string
     */
    public function get($name, $safe = false)
    {
        if (!$this->has($name, $safe)) return '';
        return static::curlGet($this->path($name, $safe));
    }

    /**
     * 删除存储的文件
     * @param string $name 文件名称
     * @param boolean $safe 安全模式
     * @return boolean
     */
    public function del($name, $safe = false)
    {
        if ($this->has($name, $safe)) {
            return @unlink($this->path($name, $safe));
        } else {
            return false;
        }
    }

    /**
     * 检查文件是否已经存在
     * @param string $name 文件名称
     * @param boolean $safe 安全模式
     * @return boolean
     */
    public function has($name, $safe = false)
    {
        return file_exists($this->path($name, $safe));
    }

    /**
     * 获取文件当前URL地址
     * @param string $name 文件名称
     * @param boolean $safe 安全模式
     * @param string $attname 下载名称
     * @return string|null
     */
    public function url($name, $safe = false, $attname = null)
    {
        return $safe ? $name : "{$this->prefix}/upload/{$this->delSuffix($name)}{$this->getSuffix($attname)}";
    }

    /**
     * 获取文件存储路径
     * @param string $name 文件名称
     * @param boolean $safe 安全模式
     * @return string
     */
    public function path($name, $safe = false)
    {
        $root = $this->app->getRootPath();
        $path = $safe ? 'safefile' : 'public/upload';
        return strtr("{$root}{$path}/{$this->delSuffix($name)}", '\\', '/');
    }

    /**
     * 获取文件存储信息
     * @param string $name 文件名称
     * @param boolean $safe 安全模式
     * @param string $attname 下载名称
     * @return array
     */
    public function info($name, $safe = false, $attname = null)
    {
        return $this->has($name, $safe) ? [
            'url' => $this->url($name, $safe, $attname),
            'key' => "upload/{$name}", 'file' => $this->path($name, $safe),
        ] : [];
    }

    /**
     * 获取文件上传地址
     * @return string
     */
    public function upload()
    {
        return url('api/upload/file')->build();
    }

}