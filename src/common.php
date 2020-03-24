<?php

// +----------------------------------------------------------------------
// | Library for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2020 广州楚才信息科技有限公司 [ http://www.cuci.cc ]
// +----------------------------------------------------------------------
// | 官方网站: https://gitee.com/zoujingli/ThinkLibrary
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// +----------------------------------------------------------------------
// | gitee 仓库地址 ：https://gitee.com/zoujingli/ThinkLibrary
// | github 仓库地址 ：https://github.com/zoujingli/ThinkLibrary
// +----------------------------------------------------------------------

use think\admin\extend\HttpExtend;
use think\admin\service\AdminService;
use think\admin\service\QueueService;
use think\admin\service\SystemService;
use think\admin\service\TokenService;
use think\admin\Storage;
use think\db\Query;

if (!function_exists('p')) {
    /**
     * 打印输出数据到文件
     * @param mixed $data 输出的数据
     * @param boolean $new 强制替换文件
     * @param string $file 保存文件名称
     */
    function p($data, $new = false, $file = null)
    {
        SystemService::instance()->putDebug($data, $new, $file);
    }
}
if (!function_exists('auth')) {
    /**
     * 访问权限检查
     * @param string $node
     * @return boolean
     * @throws ReflectionException
     */
    function auth($node)
    {
        return AdminService::instance()->check($node);
    }
}
if (!function_exists('sysconf')) {
    /**
     * 获取或配置系统参数
     * @param string $name 参数名称
     * @param string $value 参数内容
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    function sysconf($name = '', $value = null)
    {
        if (is_null($value) && is_string($name)) {
            return SystemService::instance()->get($name);
        } else {
            return SystemService::instance()->set($name, $value);
        }
    }
}
if (!function_exists('sysdata')) {
    /**
     * JSON 数据读取与存储
     * @param string $name 数据名称
     * @param mixed $value 数据内容
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    function sysdata($name, $value = null)
    {
        if (is_null($value)) {
            return SystemService::instance()->getData($name);
        } else {
            return SystemService::instance()->setData($name, $value);
        }
    }
}
if (!function_exists('sysqueue')) {
    /**
     * 注册异步处理任务
     * @param string $title 任务名称
     * @param string $command 执行内容
     * @param integer $later 延时执行时间
     * @param array $data 任务附加数据
     * @param integer $rscript 任务类型(0单例,1多例)
     * @param integer $loops 循环等待时间
     * @return string
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    function sysqueue($title, $command, $later = 0, $data = [], $rscript = 1, $loops = 0)
    {
        return QueueService::instance()->register($title, $command, $later, $data, $rscript, $loops)->code;
    }
}
if (!function_exists('systoken')) {
    /**
     * 生成 CSRF-TOKEN 参数
     * @param string $node
     * @return string
     */
    function systoken($node = null)
    {
        $result = TokenService::instance()->buildFormToken($node);
        return isset($result['token']) ? $result['token'] : '';
    }
}
if (!function_exists('sysoplog')) {
    /**
     * 写入系统日志
     * @param string $action 日志行为
     * @param string $content 日志内容
     * @return boolean
     */
    function sysoplog($action, $content)
    {
        return SystemService::instance()->setOplog($action, $content);
    }
}
if (!function_exists('encode')) {
    /**
     * 加密 UTF8 字符串
     * @param string $content
     * @return string
     */
    function encode($content)
    {
        list($chars, $length) = ['', strlen($string = iconv('UTF-8', 'GBK//TRANSLIT', $content))];
        for ($i = 0; $i < $length; $i++) $chars .= str_pad(base_convert(ord($string[$i]), 10, 36), 2, 0, 0);
        return $chars;
    }
}
if (!function_exists('decode')) {
    /**
     * 解密 UTF8 字符串
     * @param string $content
     * @return string
     */
    function decode($content)
    {
        $chars = '';
        foreach (str_split($content, 2) as $char) {
            $chars .= chr(intval(base_convert($char, 36, 10)));
        }
        return iconv('GBK//TRANSLIT', 'UTF-8', $chars);
    }
}
if (!function_exists('http_get')) {
    /**
     * 以get模拟网络请求
     * @param string $url HTTP请求URL地址
     * @param array|string $query GET请求参数
     * @param array $options CURL参数
     * @return boolean|string
     */
    function http_get($url, $query = [], $options = [])
    {
        return HttpExtend::get($url, $query, $options);
    }
}
if (!function_exists('http_post')) {
    /**
     * 以post模拟网络请求
     * @param string $url HTTP请求URL地址
     * @param array|string $data POST请求数据
     * @param array $options CURL参数
     * @return boolean|string
     */
    function http_post($url, $data, $options = [])
    {
        return HttpExtend::post($url, $data, $options);
    }
}
if (!function_exists('data_save')) {
    /**
     * 数据增量保存
     * @param Query|string $dbQuery
     * @param array $data 需要保存或更新的数据
     * @param string $key 条件主键限制
     * @param array $where 其它的where条件
     * @return boolean
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    function data_save($dbQuery, $data, $key = 'id', $where = [])
    {
        return SystemService::instance()->save($dbQuery, $data, $key, $where);
    }
}
if (!function_exists('format_datetime')) {
    /**
     * 日期格式标准输出
     * @param string $datetime 输入日期
     * @param string $format 输出格式
     * @return false|string
     */
    function format_datetime($datetime, $format = 'Y年m月d日 H:i:s')
    {
        if (empty($datetime)) return '-';
        if (is_numeric($datetime)) {
            return date($format, $datetime);
        } else {
            return date($format, strtotime($datetime));
        }
    }
}
if (!function_exists('enbase64url')) {
    /**
     * Base64安全URL编码
     * @param string $string
     * @return string
     */
    function enbase64url(string $string)
    {
        return rtrim(strtr(base64_encode($string), '+/', '-_'), '=');
    }
}
if (!function_exists('debase64url')) {
    /**
     * Base64安全URL解码
     * @param string $string
     * @return string
     */
    function debase64url(string $string)
    {
        return base64_decode(str_pad(strtr($string, '-_', '+/'), strlen($string) % 4, '=', STR_PAD_RIGHT));
    }
}
if (!function_exists('down_file')) {
    /**
     * 下载远程文件到本地
     * @param string $source 远程文件地址
     * @param boolean $force 是否强制重新下载
     * @param integer $expire 强制本地存储时间
     * @return string
     */
    function down_file($source, $force = false, $expire = 0)
    {
        $result = Storage::down($source, $force, $expire);
        return isset($result['url']) ? $result['url'] : $source;
    }
}
if (!function_exists('format_bytes')) {
    /**
     * 文件字节单位转换
     * @param integer $size
     * @return string
     */
    function format_bytes($size)
    {
        if (is_numeric($size)) {
            $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
            for ($i = 0; $size >= 1024 && $i < 4; $i++) $size /= 1024;
            return round($size, 2) . ' ' . $units[$i];
        } else {
            return $size;
        }
    }
}