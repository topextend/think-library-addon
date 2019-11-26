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

namespace think\admin;

use think\App;
use think\Container;
use think\Exception;

/**
 * 文件存储引擎管理
 * Class Storage
 * @package think\admin
 * @method array info($name, $safe = false) static 文件存储信息
 * @method string get($name, $safe = false) static 读取文件内容
 * @method string url($name, $safe = false) static 获取文件链接
 * @method string path($name, $safe = false) static 文件存储路径
 * @method boolean del($name, $safe = false) static 删除存储文件
 * @method boolean has($name, $safe = false) static 检查文件是否存在
 * @method string set($name, $file, $safe = false) static 文件储存
 * @method string upload() static 上传目录地址
 */
abstract class Storage
{
    /**
     * 应用实例
     * @var App
     */
    protected $app;

    /**
     * 存储域名前缀
     * @var string
     */
    protected $prefix;

    /**
     * Storage constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 存储初始化
     * @return Storage
     */
    protected function initialize(): Storage
    {
        return $this;
    }

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
        if (method_exists($class = self::instance(), $method)) {
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
    public static function instance($name = null): Storage
    {
        $class = ucfirst(strtolower(is_null($name) ? sysconf('storage.type') : $name));
        if (class_exists($object = "think\\admin\\storage\\{$class}Storage")) {
            return Container::getInstance()->make($object)->initialize();
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
        empty($ext) && $ext = pathinfo($url, 4);
        empty($ext) || $ext = trim($ext, '.\\/');
        empty($pre) || $pre = trim($pre, '.\\/');
        $splits = array_merge([$pre], str_split($fun($url), 16));
        return trim(join('/', $splits), '/') . '.' . strtolower($ext ? $ext : 'tmp');
    }

    /**
     * 根据文件后缀获取文件MINE
     * @param array $exts 文件后缀
     * @param array $mime 文件MINE信息
     * @return string
     */
    public static function mime($exts, $mime = [])
    {
        $mimes = self::mimes();
        foreach (is_string($exts) ? explode(',', $exts) : $exts as $e) {
            $mime[] = isset($mimes[strtolower($e)]) ? $mimes[strtolower($e)] : 'application/octet-stream';
        }
        return join(',', array_unique($mime));
    }

    /**
     * 获取所有文件扩展的MINES
     * @return array
     */
    public static function mimes()
    {
        static $mimes = [];
        if (count($mimes) > 0) return $mimes;
        return $mimes = include __DIR__ . '/storage/bin/mimes.php';
    }

}