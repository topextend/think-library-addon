<?php
// -----------------------------------------------------------------------
// |Author       : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Date         : 2020-07-08 16:36:17
// |----------------------------------------------------------------------
// |LastEditTime : 2020-07-08 17:26:27
// |----------------------------------------------------------------------
// |LastEditors  : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Description  : Class InstallService
// |----------------------------------------------------------------------
// |FilePath     : \think-library\src\service\InstallService.php
// |----------------------------------------------------------------------
// |Copyright (c) 2020 http://www.ladmin.cn   All rights reserved. 
// -----------------------------------------------------------------------
namespace think\admin\service;

use think\admin\extend\HttpExtend;
use think\admin\Service;

/**
 * 模块安装服务管理
 * Class InstallService
 * @package think\admin\service
 */
class InstallService extends Service
{
    /**
     * 项目根目录
     * @var string
     */
    protected $root;

    /**
     * 线上服务器地址
     * @var string
     */
    protected $server;

    /**
     * 当前大版本号
     * @var string
     */
    protected $version;

    /**
     * 更新规则
     * @var array
     */
    protected $rules = [];

    /**
     * 忽略规则
     * @var array
     */
    protected $ignore = [];

    /**
     * 初始化服务
     */
    protected function initialize()
    {
        // 应用框架版本
        $this->version = $this->app->config->get('app.padmin_ver') ?: 'v4';
        // 线上应用代码
        $this->server = "https://{$this->version}.padmin.cn";
        // 应用根目录
        $this->root = strtr($this->app->getRootPath(), '\\', '/');
    }

    /**
     * 获取线上接口
     * @return string
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * 获取当前版本
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * 下载并更新文件
     * @param array $file 文件信息
     * @return array
     */
    public function updateFileByDownload(array $file): array
    {
        if (in_array($file['type'], ['add', 'mod'])) {
            if ($this->downloadFile(encode($file['name']))) {
                return [true, $file['type'], $file['name']];
            } else {
                return [false, $file['type'], $file['name']];
            }
        } elseif (in_array($file['type'], ['del'])) {
            $real = $this->root . $file['name'];
            if (is_file($real) && unlink($real)) {
                $this->removeEmptyDirectory(dirname($real));
                return [true, $file['type'], $file['name']];
            } else {
                return [false, $file['type'], $file['name']];
            }
        }
    }

    /**
     * 下载更新文件内容
     * @param string $encode
     * @return boolean|integer
     */
    private function downloadFile($encode)
    {
        $source = "{$this->server}/api/update/get?encode={$encode}";
        $result = json_decode(HttpExtend::get($source), true);
        if (empty($result['code'])) return false;
        $filename = $this->root . decode($encode);
        file_exists(dirname($filename)) || mkdir(dirname($filename), 0755, true);
        return file_put_contents($filename, base64_decode($result['data']['content']));
    }

    /**
     * 清理空目录
     * @param string $path
     */
    private function removeEmptyDirectory($path)
    {
        if (is_dir($path) && count(scandir($path)) === 2 && rmdir($path)) {
            $this->removeEmptyDirectory(dirname($path));
        }
    }

    /**
     * 获取文件差异数据
     * @param array $rules 文件规则
     * @param array $ignore 忽略规则
     * @return array
     */
    public function grenerateDifference(array $rules = [], array $ignore = []): array
    {
        [$this->rules, $this->ignore, $data] = [$rules, $ignore, []];
        $result = json_decode(HttpExtend::post("{$this->server}/api/update/node", [
            'rules' => json_encode($this->rules), 'ignore' => json_encode($this->ignore),
        ]), true);
        if (!empty($result['code'])) {
            $new = $this->getList($result['data']['rules'], $result['data']['ignore']);
            foreach ($this->grenerateDifferenceContrast($result['data']['list'], $new['list']) as $file) {
                if (in_array($file['type'], ['add', 'del', 'mod'])) foreach ($this->rules as $rule) {
                    if (stripos($file['name'], $rule) === 0) $data[] = $file;
                }
            }
        }
        return $data;
    }

    /**
     * 两二维数组对比
     * @param array $serve 线上文件列表信息
     * @param array $local 本地文件列表信息
     * @return array
     */
    private function grenerateDifferenceContrast(array $serve = [], array $local = []): array
    {
        // 数据扁平化
        [$_serve, $_local, $_diffy] = [[], [], []];
        foreach ($serve as $t) $_serve[$t['name']] = $t;
        foreach ($local as $t) $_local[$t['name']] = $t;
        unset($serve, $local);
        // 线上数据差异计算
        foreach ($_serve as $t) isset($_local[$t['name']]) ? array_push($_diffy, [
            'type' => $t['hash'] === $_local[$t['name']]['hash'] ? null : 'mod', 'name' => $t['name'],
        ]) : array_push($_diffy, ['type' => 'add', 'name' => $t['name']]);
        // 本地数据增量计算
        foreach ($_local as $t) if (!isset($_serve[$t['name']])) array_push($_diffy, ['type' => 'del', 'name' => $t['name']]);
        unset($_serve, $_local);
        usort($_diffy, function ($a, $b) {
            return $a['name'] !== $b['name'] ? ($a['name'] > $b['name'] ? 1 : -1) : 0;
        });
        return $_diffy;
    }

    /**
     * 获取文件信息列表
     * @param array $rules 文件规则
     * @param array $ignore 忽略规则
     * @param array $data 扫描结果列表
     * @return array
     */
    public function getList(array $rules, array $ignore = [], array $data = []): array
    {
        // 扫描规则文件
        foreach ($rules as $key => $rule) {
            $name = strtr(trim($rule, '\\/'), '\\', '/');
            $data = array_merge($data, $this->scanList("{$this->root}{$name}"));
        }
        // 清除忽略文件
        foreach ($data as $key => $item) foreach ($ignore as $igr) {
            if (stripos($item['name'], $igr) === 0) unset($data[$key]);
        }
        // 返回文件数据
        return ['rules' => $rules, 'ignore' => $ignore, 'list' => $data];
    }

    /**
     * 获取目录文件列表
     * @param string $path 待扫描的目录
     * @param array $data 扫描结果
     * @return array
     */
    private function scanList($path, $data = []): array
    {
        if (file_exists($path)) if (is_dir($path)) foreach (scandir($path) as $sub) {
            if (strpos($sub, '.') !== 0) if (is_dir($temp = "{$path}/{$sub}")) {
                $data = array_merge($data, $this->scanList($temp));
            } else {
                array_push($data, $this->getInfo($temp));
            }
        } else {
            return [$this->getInfo($path)];
        }
        return $data;
    }

    /**
     * 获取指定文件信息
     * @param string $realname 文件路径
     * @return array
     */
    private function getInfo($realname): array
    {
        return [
            'name' => str_replace($this->root, '', $realname),
            'hash' => md5(preg_replace('/\s+/', '', file_get_contents($realname))),
        ];
    }
}