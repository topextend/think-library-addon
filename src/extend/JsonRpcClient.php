<?php
// -----------------------------------------------------------------------
// |Author       : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Date         : 2020-07-08 16:36:17
// |----------------------------------------------------------------------
// |LastEditTime : 2020-08-13 19:41:43
// |----------------------------------------------------------------------
// |LastEditors  : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Description  : Class JsonRpcClient
// |----------------------------------------------------------------------
// |FilePath     : \think-library\src\extend\JsonRpcClient.php
// |----------------------------------------------------------------------
// |Copyright (c) 2020 http://www.ladmin.cn   All rights reserved. 
// -----------------------------------------------------------------------
namespace think\admin\extend;

/**
 * JsonRpc 客户端
 * Class JsonRpcClient
 * @package think\admin\extend
 */
class JsonRpcClient
{
    /**
     * 请求ID
     * @var integer
     */
    private $id;

    /**
     * 服务端地址
     * @var string
     */
    private $proxy;

    /**
     * JsonRpcClient constructor.
     * @param $proxy
     */
    public function __construct($proxy)
    {
        $this->proxy = $proxy;
        $this->id = CodeExtend::uniqidNumber();
    }

    /**
     * 执行 JsonRpc 请求
     * @param string $method
     * @param array $params
     * @return mixed
     * @throws \think\admin\Exception
     */
    public function __call($method, $params)
    {
        // Performs the HTTP POST
        $options = [
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-type: application/json',
                'content' => json_encode([
                    'jsonrpc' => '2.0', 'method' => $method, 'params' => $params, 'id' => $this->id,
                ], JSON_UNESCAPED_UNICODE),
            ],
        ];
        if ($fp = fopen($this->proxy, 'r', false, stream_context_create($options))) {
            $response = '';
            while ($row = fgets($fp)) $response .= trim($row) . "\n";
            fclose($fp);
            $response = json_decode($response, true);
        } else {
            throw new \think\admin\Exception("无法连接到 {$this->proxy}");
        }
        // Final checks and return
        if ($response['id'] != $this->id) {
            throw new \think\admin\Exception("错误的响应标记 (请求标记: {$this->id}, 响应标记: {$response['id']}）");
        }
        if (is_null($response['error'])) {
            return $response['result'];
        } else {
            throw new \think\admin\Exception("请求错误：{$response['error']['message']}", $response['error']['code']);
        }
    }
}