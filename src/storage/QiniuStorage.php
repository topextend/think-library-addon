<?php
// -----------------------------------------------------------------------
// |Author       : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Date         : 2020-07-08 16:36:17
// |----------------------------------------------------------------------
// |LastEditTime : 2020-08-13 19:46:20
// |----------------------------------------------------------------------
// |LastEditors  : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Description  : Class QiniuStorage
// |----------------------------------------------------------------------
// |FilePath     : \think-library\src\storage\QiniuStorage.php
// |----------------------------------------------------------------------
// |Copyright (c) 2020 http://www.ladmin.cn   All rights reserved. 
// -----------------------------------------------------------------------
namespace think\admin\storage;

use think\admin\extend\HttpExtend;
use think\admin\Storage;

/**
 * 七牛云存储支持
 * Class QiniuStorage
 * @package think\admin\storage
 */
class QiniuStorage extends Storage
{
    private $bucket;
    private $accessKey;
    private $secretKey;

    /**
     * 初始化入口
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function initialize()
    {
        // 读取配置文件
        $this->bucket = sysconf('storage.qiniu_bucket');
        $this->accessKey = sysconf('storage.qiniu_access_key');
        $this->secretKey = sysconf('storage.qiniu_secret_key');
        // 计算链接前缀
        $type = strtolower(sysconf('storage.qiniu_http_protocol'));
        $domain = strtolower(sysconf('storage.qiniu_http_domain'));
        if ($type === 'auto') $this->prefix = "//{$domain}";
        elseif ($type === 'http') $this->prefix = "http://{$domain}";
        elseif ($type === 'https') $this->prefix = "https://{$domain}";
        else throw new \think\admin\Exception('未配置七牛云URL域名哦');
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
        return parent::instance('qiniu');
    }

    /**
     * 上传文件内容
     * @param string $name 文件名称
     * @param string $file 文件内容
     * @param boolean $safe 安全模式
     * @param string $attname 下载名称
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function set($name, $file, $safe = false, $attname = null)
    {
        $token = $this->buildUploadToken($name, 3600, $attname);
        $data = ['key' => $name, 'token' => $token, 'fileName' => $name];
        $file = ['field' => "file", 'name' => $name, 'content' => $file];
        $result = HttpExtend::submit($this->upload(), $data, $file, [], 'POST', false);
        return json_decode($result, true);
    }


    /**
     * 根据文件名读取文件内容
     * @param string $name 文件名称
     * @param boolean $safe 安全模式
     * @return string
     */
    public function get($name, $safe = false)
    {
        $url = $this->url($name, $safe) . "?e=" . time();
        $token = "{$this->accessKey}:{$this->safeBase64(hash_hmac('sha1', $url, $this->secretKey, true))}";
        return static::curlGet("{$url}&token={$token}");
    }

    /**
     * 删除存储的文件
     * @param string $name 文件名称
     * @param boolean $safe 安全模式
     * @return boolean|null
     */
    public function del($name, $safe = false)
    {
        list($EncodedEntryURI, $AccessToken) = $this->getAccessToken($name, 'delete');
        $data = json_decode(HttpExtend::post("http://rs.qiniu.com/delete/{$EncodedEntryURI}", [], [
            'headers' => ["Authorization:QBox {$AccessToken}"],
        ]), true);
        return empty($data['error']);
    }

    /**
     * 检查文件是否已经存在
     * @param string $name 文件名称
     * @param boolean $safe 安全模式
     * @return boolean
     */
    public function has($name, $safe = false)
    {
        return is_array($this->info($name, $safe));
    }

    /**
     * 获取文件当前URL地址
     * @param string $name 文件名称
     * @param boolean $safe 安全模式
     * @param string $attname 下载名称
     * @return string
     */
    public function url($name, $safe = false, $attname = null)
    {
        return "{$this->prefix}/{$this->delSuffix($name)}{$this->getSuffix($attname)}";
    }

    /**
     * 获取文件存储路径
     * @param string $name 文件名称
     * @param boolean $safe 安全模式
     * @return string
     */
    public function path($name, $safe = false)
    {
        return $this->url($name, $safe);
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
        list($entry, $token) = $this->getAccessToken($name);
        $data = json_decode(HttpExtend::get("http://rs.qiniu.com/stat/{$entry}", [], ['headers' => ["Authorization: QBox {$token}"]]), true);
        return isset($data['md5']) ? ['file' => $name, 'url' => $this->url($name, $safe, $attname), 'key' => $name] : [];
    }

    /**
     * 获取文件上传地址
     * @return string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function upload()
    {
        $protocol = $this->app->request->isSsl() ? 'https' : 'http';
        switch (sysconf('storage.qiniu_region')) {
            case '华东':
                return "{$protocol}://up.qiniup.com";
            case '华北':
                return "{$protocol}://up-z1.qiniup.com";
            case '华南':
                return "{$protocol}://up-z2.qiniup.com";
            case '北美':
                return "{$protocol}://up-na0.qiniup.com";
            case '东南亚':
                return "{$protocol}://up-as0.qiniup.com";
            default:
                throw new \think\Exception('未配置七牛云空间区域哦');
        }
    }

    /**
     * 获取文件上传令牌
     * @param string $name 文件名称
     * @param integer $expires 有效时间
     * @param string $attname 下载名称
     * @return string
     */
    public function buildUploadToken($name = null, $expires = 3600, $attname = null)
    {
        $policy = $this->safeBase64(json_encode([
            "deadline"   => time() + $expires, "scope" => is_null($name) ? $this->bucket : "{$this->bucket}:{$name}",
            'returnBody' => json_encode([
                'uploaded' => true, 'filename' => '$(key)', 'url' => "{$this->prefix}/$(key){$this->getSuffix($attname)}", 'key' => $name, 'file' => $name,
            ], JSON_UNESCAPED_UNICODE),
        ]));
        return "{$this->accessKey}:{$this->safeBase64(hash_hmac('sha1', $policy, $this->secretKey, true))}:{$policy}";
    }

    /**
     * URL安全的Base64编码
     * @param string $content
     * @return string
     */
    private function safeBase64($content)
    {
        return str_replace(['+', '/'], ['-', '_'], base64_encode($content));
    }

    /**
     * 获取对象管理凭证
     * @param string $name 文件名称
     * @param string $type 操作类型
     * @return array
     */
    private function getAccessToken($name, $type = 'stat')
    {
        $entry = $this->safeBase64("{$this->bucket}:{$name}");
        $sign = hash_hmac('sha1', "/{$type}/{$entry}\n", $this->secretKey, true);
        return [$entry, "{$this->accessKey}:{$this->safeBase64($sign)}"];
    }
}