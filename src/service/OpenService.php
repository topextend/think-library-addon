<?php
// -----------------------------------------------------------------------
// |Author       : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Date         : 2020-07-08 16:36:17
// |----------------------------------------------------------------------
// |LastEditTime : 2020-08-13 19:45:09
// |----------------------------------------------------------------------
// |LastEditors  : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Description  : Class OpenService
// |----------------------------------------------------------------------
// |FilePath     : \think-library\src\service\OpenService.php
// |----------------------------------------------------------------------
// |Copyright (c) 2020 http://www.ladmin.cn   All rights reserved. 
// -----------------------------------------------------------------------
namespace think\admin\service;

use think\admin\extend\HttpExtend;
use think\admin\Service;
use think\App;

/**
 * 开放平台服务
 * Class OpenService
 * @package think\admin\service
 */
class OpenService extends Service
{
    /**
     * 接口账号
     * @var string
     */
    protected $appid;

    /**
     * 接口密钥
     * @var string
     */
    protected $appkey;

    /**
     * 开放平台初始化
     * OpenService constructor.
     * @param App $app
     * @param string $appid 接口账号
     * @param string $appkey 接口密钥
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function __construct(App $app, $appid = '', $appkey = '')
    {
        parent::__construct($app);
        $this->appid = $appid ?: sysconf('data.cuci_open_appid');
        $this->appkey = $appkey ?: sysconf('data.cuci_open_appkey');
    }

    /**
     * 接口数据签名
     * @param array $data [time, nostr, json, sign]
     * @return array
     */
    public function signData(array $data): array
    {
        [$time, $nostr, $json] = [time(), uniqid(), json_encode($data, JSON_UNESCAPED_UNICODE)];
        return [$time, $nostr, $json, md5("{$this->appid}#{$json}#{$time}#{$this->appkey}#{$nostr}")];
    }

    /**
     * 接口数据请求
     * @param string $uri 接口地址
     * @param array $data 请求数据
     * @return array
     * @throws \think\admin\Exception
     */
    public function doRequest(string $uri, array $data = []): array
    {
        [$time, $nostr, $json, $sign] = $this->signData($data);
        $post = ['appid' => $this->appid, 'time' => $time, 'nostr' => $nostr, 'sign' => $sign, 'data' => $json];
        $result = json_decode(HttpExtend::post("https://open.padmin.cn/{$uri}", $post), true);
        if (empty($result)) throw new \think\admin\Exception('服务端接口响应异常');
        if (empty($result['code'])) throw new \think\admin\Exception($result['info']);
        return $result['data'] ?? [];
    }
}