<?php
// -----------------------------------------------------------------------
// |Author       : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Date         : 2020-07-08 16:36:17
// |----------------------------------------------------------------------
// |LastEditTime : 2020-08-13 19:47:22
// |----------------------------------------------------------------------
// |LastEditors  : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Description  : Class Library
// |----------------------------------------------------------------------
// |FilePath     : \think-library\src\Library.php
// |----------------------------------------------------------------------
// |Copyright (c) 2020 http://www.ladmin.cn   All rights reserved. 
// -----------------------------------------------------------------------
namespace think\admin;

use think\admin\command\Database;
use think\admin\command\Install;
use think\admin\command\Queue;
use think\admin\command\Version;
use think\admin\multiple\App;
use think\admin\multiple\command\Build;
use think\admin\multiple\command\Clear;
use think\admin\multiple\Url;
use think\admin\service\AdminService;
use think\admin\service\SystemService;
use think\middleware\LoadLangPack;
use think\middleware\SessionInit;
use think\Request;
use think\Service;
use think\Route;
use think\facade\Db;
use think\facade\Cache;
use think\facade\Event;
use think\facade\Config;
use think\admin\addons\middleware\Addons;
use function Composer\Autoload\includeFile;

/**
 * 模块注册服务
 * Class Library
 * @package think\admin
 */
class Library extends Service
{
    // 定义插件目录
    protected $addons_path;

    /**
     * 启动服务
     */
    public function boot()
    {
        // 多应用中间键
        $this->app->event->listen('HttpRun', function () {
            $this->app->middleware->add(App::class);
        });
        // 替换 ThinkPHP 地址处理
        $this->app->bind('think\route\Url', Url::class);
        // 替换 ThinkPHP 指令
        $this->commands(['build' => Build::class, 'clear' => Clear::class]);
        // 注册 ThinkAdmin 指令
        $this->commands([Queue::class, Install::class, Version::class, Database::class]);
        // 绑定插件路由
        $this->bindAddonsRoutes();
        // 动态应用运行参数
        SystemService::instance()->bindRuntime();
    }

    /**
     * 初始化服务
     */
    public function register()
    {
        // 插件目录
        define('ADDON_PATH', root_path() . 'addons' . DIRECTORY_SEPARATOR);
        define('RUNTIME_PATH', root_path() . 'runtime' . DIRECTORY_SEPARATOR);
        // 输入默认过滤
        $this->app->request->filter(['trim']);
        // 加载中文语言
        $this->app->lang->load(__DIR__ . '/lang/zh-cn.php', 'zh-cn');
        $this->app->lang->load(__DIR__ . '/lang/en-us.php', 'en-us');
        // 判断访问模式，兼容 CLI 访问控制器
        if ($this->app->request->isCli()) {
            if (empty($_SERVER['REQUEST_URI']) && isset($_SERVER['argv'][1])) {
                $this->app->request->setPathinfo($_SERVER['argv'][1]);
            }
        } else {
            $isSess = $this->app->request->request('not_init_session', 0) == 0;
            $notYar = stripos($this->app->request->header('user-agent', ''), 'PHP Yar RPC-') === false;
            if ($notYar && $isSess) {
                // 注册会话初始化中间键
                $this->app->middleware->add(SessionInit::class);
                // 注册语言包处理中间键
                $this->app->middleware->add(LoadLangPack::class);
            }
            // 注册访问处理中间键
            $this->app->middleware->add(function (Request $request, \Closure $next) {
                $header = [];
                if (($origin = $request->header('origin', '*')) !== '*') {
                    $header['Access-Control-Allow-Origin'] = $origin;
                    $header['Access-Control-Allow-Methods'] = 'GET,POST,PATCH,PUT,DELETE';
                    $header['Access-Control-Allow-Headers'] = 'Authorization,Content-Type,If-Match,If-Modified-Since,If-None-Match,If-Unmodified-Since,X-Requested-With';
                    $header['Access-Control-Expose-Headers'] = 'User-Form-Token,User-Token,Token';
                }
                // 访问模式及访问权限检查
                if ($request->isOptions()) {
                    return response()->code(204)->header($header);
                } elseif (AdminService::instance()->check()) {
                    return $next($request)->header($header);
                } elseif (AdminService::instance()->isLogin()) {
                    return json(['code' => 0, 'msg' => lang('think_library_not_auth')])->header($header);
                } else {
                    return json(['code' => 0, 'msg' => lang('think_library_not_login'), 'url' => sysuri('admin/login/index')])->header($header);
                }
            }, 'route');
        }
        // 创建插件目录
        $this->addons_path = $this->getAddonsPath();
        // 数据库加载钩子
        $this->database();
        // 自动载入插件
        $this->autoload();
        // 加载插件事件
        $this->loadEvent();
        // 加载插件系统服务
        $this->loadService();
        // 绑定插件容器
        $this->app->bind('addons', Service::class);
        // 动态加入应用函数
        $SysRule = "{$this->app->getBasePath()}*/sys.php";
        foreach (glob($SysRule) as $file) includeFile($file);
    }

    /**
     * 绑定插件路由设置     *
     * @return void
     */
    private function bindAddonsRoutes()
    {
        $this->registerRoutes(function (Route $route)
        {
            // 路由脚本
            $execute = '\\think\\admin\\addons\\Route@execute';
            // 注册插件公共中间件
            if (is_file($this->getAddonsPath() . 'middleware.php')) {
                $this->app->middleware->import(include $this->getAddonsPath() . 'middleware.php', 'route');
            }
            // 定义插件路径            
            $addonsDir = Config::get('addons.dir', 'addons');
            // 注册控制器路由
            $route->rule($addonsDir . "/:addon/[:controller]/[:action]", $execute)->middleware(Addons::class);
            // 自定义路由
            $routes = (array) Config::get('addons.route', []);
            foreach ($routes as $key => $val) {
                if (!$val) {
                    continue;
                }
                if (is_array($val)) {
                    $domain = $val['domain'];
                    $rules = [];
                    foreach ($val['rule'] as $k => $rule) {
                        [$addon, $controller, $action] = explode('/', $rule);
                        $rules[$k] = [
                            'addons'        => $addon,
                            'controller'    => $controller,
                            'action'        => $action,
                            'indomain'      => 1,
                        ];
                    }
                    $route->domain($domain, function () use ($rules, $route, $execute) {
                        // 动态注册域名的路由规则
                        foreach ($rules as $k => $rule) {
                            $route->rule($k, $execute)
                                ->name($k)
                                ->completeMatch(true)
                                ->append($rule);
                        }
                    });
                } else {
                    [ $addon, $controller, $action ] = explode('/', $val);
                    $route->rule($key, $execute)
                        ->name($key)
                        ->completeMatch(true)
                        ->append([
                            'addons' => $addon,
                            'controller' => $controller,
                            'action' => $action
                        ]);
                }
            }
        });
    }
    
    /**
     * 插件事件
     * @return void
     */
    private function loadEvent()
    {
        $hooks = $this->app->isDebug() ? [] : Cache::get('hooks', []);
        if (empty($hooks)) {
            $hooks = (array) Config::get('addons.hooks', []);
            // 初始化钩子
            foreach ($hooks as $key => $values) {
                if (is_string($values)) {
                    $values = explode(',', $values);
                } else {
                    $values = (array) $values;
                }
                $hooks[$key] = array_filter(array_map(function ($v) use ($key) {
                    return [get_addons_class($v), $key];
                }, $values));
            }
            Cache::set('hooks', $hooks);
        }
        //如果在插件中有定义 AddonsInit，则直接执行
        if (isset($hooks['AddonsInit'])) {
            foreach ($hooks['AddonsInit'] as $k => $v) {
                Event::trigger('AddonsInit', $v);
            }
        }
        Event::listenEvents($hooks);
    }

    /**
     * 挂载插件服务     *
     * @return void
     */
    private function loadService()
    {
        $results = scandir($this->addons_path);
        $bind = [];
        foreach ($results as $name) {
            if ($name === '.' or $name === '..') {
                continue;
            }
            if (is_file($this->addons_path . $name)) {
                continue;
            }
            $addonDir = $this->addons_path . $name . DIRECTORY_SEPARATOR;
            if (!is_dir($addonDir)) {
                continue;
            }

            if (!is_file($addonDir . ucfirst($name) . '.php')) {
                continue;
            }

            $service_file = $addonDir . 'service.ini';
            if (!is_file($service_file)) {
                continue;
            }
            $info = parse_ini_file($service_file, true, INI_SCANNER_TYPED) ?: [];
            $bind = array_merge($bind, $info);
        }
        $this->app->bind($bind);
    }

    
    /**
     * 数据库获取插件钩子列表
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function database()
    {
        $config   = Config::get('addons');
        if (!empty($config)){
            $database = $config['database'];
            //是否使用数据库加载钩子信息
            if (!$database) {
                return true;
            }
            $data = Db::name($database['table'])->where('status', 1)->cache($database['cache'], $database['expire'])->field(implode(',', $database['field']))->select();
            if (!$data->isEmpty()) {
                foreach ($data as $key => $row) {
                    $config['hooks'] += [
                        $row[ $database['field'][0] ] => $row[ $database['field'][1] ]
                    ];
                }
                Config::set($config, 'addons');
            }
        }
    }
    
    /**
     * 自动载入插件
     * @return bool
     */
    private function autoload()
    {
        // 是否处理自动载入
        if (!Config::get('addons.autoload', true)) {
            return true;
        }
        $config = Config::get('addons');
        // 读取插件目录及钩子列表
        $base = get_class_methods("\\think\\admin\\Addons");
        // 读取插件目录中的php文件
        foreach (glob($this->getAddonsPath() . '*/*.php') as $addons_file) {
            // 格式化路径信息
            $info = pathinfo($addons_file);
            // 获取插件目录名
            $name = pathinfo($info['dirname'], PATHINFO_FILENAME);
            // 找到插件入口文件
            if (strtolower($info['filename']) === $name) {
                // 读取出所有公共方法
                $addonsDir = Config::get('addons.dir', 'addons');
                $methods   = (array) get_class_methods("\\" . $addonsDir . "\\" . $name . "\\" . $info['filename']);
                // 跟插件基类方法做比对，得到差异结果
                $hooks = array_diff($methods, $base);
                // 循环将钩子方法写入配置中
                foreach ($hooks as $hook) {
                    if (!isset($config['hooks'][$hook])) {
                        $config['hooks'][$hook] = [];
                    }
                    // 兼容手动配置项
                    if (is_string($config['hooks'][$hook])) {
                        $config['hooks'][$hook] = explode(',', $config['hooks'][$hook]);
                    }
                    if (!in_array($name, $config['hooks'][$hook])) {
                        $config['hooks'][$hook][] = $name;
                    }
                }
            }
        }
        Config::set($config, 'addons');
    }
    
    /**
     * 获取 addons 路径
     * @return string
     */
    public function getAddonsPath()
    {
        $addonsDir = Config::get('addons.dir', 'addons');
        // 初始化插件目录
        $addons_path = $this->app->getRootPath() . $addonsDir . DIRECTORY_SEPARATOR;
        // 如果插件目录不存在则创建
        if (!is_dir($addons_path)) {
            @mkdir($addons_path, 0755, true);
        }
        return $addons_path;
    }

    /**
     * 获取插件的配置信息
     * @return array
     */
    public function getAddonsConfig()
    {
        $name = $this->app->request->addon;
        $addon = get_addons_instance($name);
        if (!$addon) {
            return [];
        }
        return $addon->getConfig();
    }
}