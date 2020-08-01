<?php
// -----------------------------------------------------------------------
// |Author       : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Date         : 2020-07-08 16:36:17
// |----------------------------------------------------------------------
// |LastEditTime : 2020-07-08 17:31:21
// |----------------------------------------------------------------------
// |LastEditors  : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Description  : Class Storage
// |----------------------------------------------------------------------
// |FilePath     : \think-library\src\Storage.php
// |----------------------------------------------------------------------
// |Copyright (c) 2020 http://www.ladmin.cn   All rights reserved. 
// -----------------------------------------------------------------------
namespace think\admin;

use think\admin\storage\AliossStorage;
use think\admin\storage\LocalStorage;
use think\admin\storage\QiniuStorage;
use think\App;
use think\Container;

/**
 * 文件存储引擎管理
 * Class Storage
 * @package think\admin
 * @method array info($name, $safe = false, $attname = null) static 文件存储信息
 * @method array set($name, $file, $safe = false, $attname = null) static 储存文件
 * @method string url($name, $safe = false, $attname = null) static 获取文件链接
 * @method string get($name, $safe = false) static 读取文件内容
 * @method string path($name, $safe = false) static 文件存储路径
 * @method boolean del($name, $safe = false) static 删除存储文件
 * @method boolean has($name, $safe = false) static 检查是否存在
 * @method string upload() static 获取上传地址
 */
abstract class Storage
{
    /**
     * 应用实例
     * @var App
     */
    protected $app;

    /**
     * 链接前缀
     * @var string
     */
    protected $prefix;

    /**
     * 链接类型
     * @var string
     */
    protected $linkType;

    /**
     * Storage constructor.
     * @param App $app
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->linkType = sysconf('storage.link_type');
        $this->initialize();
    }

    /**
     * 存储驱动初始化
     */
    abstract protected function initialize();

    /**
     * 静态访问启用
     * @param string $method 方法名称
     * @param array $arguments 调用参数
     * @return mixed
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function __callStatic($method, $arguments)
    {
        if (method_exists($class = static::instance(), $method)) {
            return call_user_func_array([$class, $method], $arguments);
        } else {
            throw new Exception("method not exists: " . get_class($class) . "->{$method}()");
        }
    }

    /**
     * 设置文件驱动名称
     * @param string $name 驱动名称
     * @return static
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function instance($name = null)
    {
        $class = ucfirst(strtolower($name ?: sysconf('storage.type')));
        if (class_exists($object = "think\\admin\\storage\\{$class}Storage")) {
            return Container::getInstance()->make($object);
        } else {
            throw new Exception("File driver [{$class}] does not exist.");
        }
    }

    /**
     * 获取文件相对名称
     * @param string $url 文件访问链接
     * @param string $ext 文件后缀名称
     * @param string $pre 文件存储前缀
     * @param string $fun 名称规则方法
     * @return string
     */
    public static function name($url, $ext = '', $pre = '', $fun = 'md5')
    {
        if (empty($ext)) $ext = pathinfo($url, 4);
        list($xmd, $ext) = [$fun($url), trim($ext, '.\\/')];
        $attr = [trim($pre, '.\\/'), substr($xmd, 0, 2), substr($xmd, 2, 30)];
        return trim(join('/', $attr), '/') . '.' . strtolower($ext ? $ext : 'tmp');
    }

    /**
     * 下载文件到本地
     * @param string $url 文件URL地址
     * @param boolean $force 是否强制下载
     * @param integer $expire 文件保留时间
     * @return array
     */
    public static function down($url, $force = false, $expire = 0)
    {
        try {
            $file = LocalStorage::instance();
            $name = static::name($url, '', 'down/');
            if (empty($force) && $file->has($name)) {
                if ($expire < 1 || filemtime($file->path($name)) + $expire > time()) {
                    return $file->info($name);
                }
            }
            return $file->set($name, static::curlGet($url));
        } catch (\Exception $exception) {
            return ['url' => $url, 'hash' => md5($url), 'key' => $url, 'file' => $url];
        }
    }

    /**
     * 根据文件后缀获取文件MINE
     * @param array|string $exts 文件后缀
     * @param array $mime 文件信息
     * @return string
     */
    public static function mime($exts, $mime = [])
    {
        $mimes = static::mimes();
        foreach (is_string($exts) ? explode(',', $exts) : $exts as $ext) {
            $mime[] = $mimes[strtolower($ext)] ?? 'application/octet-stream';
        }
        return join(',', array_unique($mime));
    }

    /**
     * 获取所有文件的信息
     * @return array
     */
    public static function mimes(): array
    {
        static $mimes = [];
        if (count($mimes) > 0) return $mimes;
        return $mimes = include __DIR__ . '/storage/bin/mimes.php';
    }

    /**
     * 使用CURL读取网络资源
     * @param string $url
     * @return string
     */
    public static function curlGet($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        list($content) = [curl_exec($ch), curl_close($ch)];
        return $content;
    }

    /**
     * 获取下载链接后缀
     * @param string $attname 下载名称
     * @return string
     */
    protected function getSuffix($attname = null)
    {
        if ($this->linkType === 'full') {
            if (is_string($attname) && strlen($attname) > 0) {
                return "?attname=" . urlencode($attname);
            }
        }
        return '';
    }

    /**
     * 获取文件基础名称
     * @param string $name 文件名称
     * @return string
     */
    protected function delSuffix($name)
    {
        if (strpos($name, '?') !== false) {
            return strstr($name, '?', true);
        }
        return $name;
    }
}