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

namespace think\admin\service;

use think\admin\extend\HttpExtend;
use think\admin\Service;

/**
 * 百度快递100物流查询
 * Class ExpressService
 * @package think\admin\service
 */
class ExpressService extends Service
{
    /**
     * 网络请求令牌
     * @var string
     */
    protected $token;

    /**
     * 网络请求参数
     * @var array
     */
    protected $options;

    /**
     * 会话Cookie文件
     * @var string
     */
    protected $cookies = '';

    /**
     * 快递服务初始化
     * @return $this
     */
    protected function initialize()
    {
        $this->cookies = "{$this->app->getRuntimePath()}_express_cookie.txt";
        if (file_exists($this->cookies) && filemtime($this->cookies) + 10 < time()) {
            @unlink($this->cookies);
        }
        $this->options = [
            'cookie_file' => $this->cookies, 'headers' => [
                'Host'            => 'express.baidu.com',
                'CLIENT-IP'       => $this->app->request->ip(),
                'X-FORWARDED-FOR' => $this->app->request->ip(),
            ],
        ];
        $this->token = $this->getExpressToken();
        return $this;
    }

    /**
     * 通过百度快递100应用查询物流信息
     * @param string $code 快递公司编辑
     * @param string $number 快递物流编号
     * @return array
     */
    public function express($code, $number)
    {
        list($list, $cache) = [[], $this->app->cache->get($ckey = md5($code . $number))];
        if (!empty($cache)) return ['message' => 'ok', 'com' => $code, 'nu' => $number, 'data' => $cache];
        for ($i = 0; $i < 6; $i++) if (is_array($result = $this->doExpress($code, $number))) {
            if (!empty($result['data']['info']['context'])) {
                foreach ($result['data']['info']['context'] as $vo) $list[] = [
                    'time' => date('Y-m-d H:i:s', $vo['time']), 'context' => $vo['desc'],
                ];
                $this->app->cache->set($ckey, $list, 10);
                return ['message' => 'ok', 'com' => $code, 'nu' => $number, 'data' => $list];
            }
        }
        return ['message' => 'ok', 'com' => $code, 'nu' => $number, 'data' => $list];
    }

    /**
     * 获取快递公司列表
     * @return array
     */
    public function getExpressList()
    {
        $data = [];
        if (preg_match('/"currentData":.*?\[(.*?)\],/', $this->getWapBaiduHtml(), $matches)) {
            foreach (json_decode("[{$matches['1']}]") as $item) $data[$item->value] = $item->text;
            unset($data['_auto']);
            return $data;
        } else {
            $this->app->cache->delete('express_kuaidi_html');
            return $this->getExpressList();
        }
    }

    /**
     * 执行百度快递100应用查询请求
     * @param string $code 快递公司编号
     * @param string $number 快递单单号
     * @return mixed
     */
    private function doExpress($code, $number)
    {
        $uniqid = strtr(uniqid(), '.', '');
        $url = "https://express.baidu.com/express/api/express?tokenV2={$this->token}&appid=4001&nu={$number}&com={$code}&qid={$uniqid}&new_need_di=1&source_xcx=0&vcode=&token=&sourceId=4155&cb=callback";
        return json_decode(str_replace('/**/callback(', '', trim(HttpExtend::get($url, [], $this->options), ')')), true);
    }

    /**
     * 获取接口请求令牌
     * @return string
     */
    private function getExpressToken()
    {
        if (preg_match('/express\?tokenV2=(.*?)",/', $this->getWapBaiduHtml(), $matches)) {
            return $matches[1];
        } else {
            $this->app->cache->delete('express_kuaidi_html');
            return $this->getExpressToken();
        }
    }

    /**
     * 获取百度WAP快递HTML（用于后面的抓取关键值）
     * @return string
     */
    private function getWapBaiduHtml()
    {
        $content = $this->app->cache->get('express_kuaidi_html');
        while (empty($content) || stristr($content, '百度安全验证') > -1 || stripos($content, 'tokenV2') === -1) {
            $content = HttpExtend::get('https://m.baidu.com/s?word=快递查询&rnd=' . uniqid(), [], $this->options);
        }
        $this->app->cache->set('express_kuaidi_html', $content, 30);
        return $content;
    }

}