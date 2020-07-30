<?php
// -----------------------------------------------------------------------
// |Author       : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Date         : 2020-07-08 16:36:17
// |----------------------------------------------------------------------
// |LastEditTime : 2020-07-30 20:35:23
// |----------------------------------------------------------------------
// |LastEditors  : Jarmin <edshop@qq.com>
// |----------------------------------------------------------------------
// |Description  : Class Version
// |----------------------------------------------------------------------
// |FilePath     : \think-library\src\multiple\App.php
// |----------------------------------------------------------------------
// |Copyright (c) 2020 http://www.ladmin.cn   All rights reserved. 
// -----------------------------------------------------------------------
namespace think\admin\multiple;

use Closure;
use think\exception\HttpException;
use think\Request;
use think\Response;

/**
 * 多应用支持
 * Class MultiApp
 * @package think\admin\multiple
 */
class App
{
    /**
     * @var \think\App
     */
    protected $app;

    /**
     * 应用名称
     * @var string
     */
    protected $name;

    /**
     * 应用路径
     * @var string
     */
    protected $path;

    /**
     * App constructor.
     * @param \think\App $app
     */
    public function __construct(\think\App $app)
    {
        $this->app = $app;
        $this->name = $this->app->http->getName();
        $this->path = $this->app->http->getPath();
    }

    /**
     * 多应用解析
     * @access public
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle($request, Closure $next)
    {
        if (!$this->parseMultiApp()) return $next($request);
        return $this->app->middleware->pipeline('app')->send($request)->then(function ($request) use ($next) {
            return $next($request);
        });
    }

    /**
     * 获取路由目录
     * @access protected
     * @return string
     */
    protected function getRoutePath(): string
    {
        return $this->app->getAppPath() . 'route' . DIRECTORY_SEPARATOR;
    }

    /**
     * 解析多应用
     * @return bool
     */
    protected function parseMultiApp(): bool
    {
        $scriptName = $this->getScriptName();
        $defaultApp = $this->app->config->get('app.default_app') ?: 'index';
        if ($this->name || ($scriptName && !in_array($scriptName, ['index', 'router', 'think']))) {
            $appName = $this->name ?: $scriptName;
            $this->app->http->setBind();
        } else {
            // 自动多应用识别
            $this->app->http->setBind(false);
            $appName = null;
            $bind = $this->app->config->get('app.domain_bind', []);
            if (!empty($bind)) {
                $domain = $this->app->request->host(true);
                $subDomain = $this->app->request->subDomain();
                if (isset($bind[$domain])) {
                    $appName = $bind[$domain];
                    $this->app->http->setBind();
                } elseif (isset($bind[$subDomain])) {
                    $appName = $bind[$subDomain];
                    $this->app->http->setBind();
                } elseif (isset($bind['*'])) {
                    $appName = $bind['*'];
                    $this->app->http->setBind();
                }
            }
            if (!$this->app->http->isBind()) {
                $path = $this->app->request->pathinfo();
                $map = $this->app->config->get('app.app_map', []);
                $deny = $this->app->config->get('app.deny_app_list', []);
                $name = current(explode('/', $path));
                if (strpos($name, '.')) {
                    $name = strstr($name, '.', true);
                }
                if (isset($map[$name])) {
                    if ($map[$name] instanceof Closure) {
                        $appName = call_user_func_array($map[$name], [$this->app]) ?: $name;
                    } else {
                        $appName = $map[$name];
                    }
                } elseif ($name && (false !== array_search($name, $map) || in_array($name, $deny))) {
                    throw new HttpException(404, 'app not exists:' . $name);
                } elseif ($name && isset($map['*'])) {
                    $appName = $map['*'];
                } else {
                    $appName = $name ?: $defaultApp;
                    $appPath = $this->path ?: $this->app->getBasePath() . $appName . DIRECTORY_SEPARATOR;
                    if (!is_dir($appPath)) {
                        $express = $this->app->config->get('app.app_express', false);
                        if ($express) {
                            $this->setApp($defaultApp);
                            return true;
                        } else {
                            return false;
                        }
                    }
                }
                if ($name) {
                    $this->app->request->setRoot('/' . $name);
                    $this->app->request->setPathinfo(strpos($path, '/') ? ltrim(strstr($path, '/'), '/') : '');
                }
            }
        }
        $this->setApp($appName ?: $defaultApp);
        return true;
    }

    /**
     * 获取当前运行入口名称
     * @access protected
     * @codeCoverageIgnore
     * @return string
     */
    protected function getScriptName(): string
    {
        if (isset($_SERVER['SCRIPT_FILENAME'])) {
            $file = $_SERVER['SCRIPT_FILENAME'];
        } elseif (isset($_SERVER['argv'][0])) {
            $file = realpath($_SERVER['argv'][0]);
        }
        return isset($file) ? pathinfo($file, PATHINFO_FILENAME) : '';
    }

    /**
     * 设置应用
     * @param string $appName
     */
    protected function setApp(string $appName): void
    {
        $this->app->http->name($appName);
        $appPath = $this->path ?: $this->app->getBasePath() . $appName . DIRECTORY_SEPARATOR;
        $this->app->setAppPath($appPath);
        // 设置应用命名空间
        $this->app->setNamespace($this->app->config->get('app.app_namespace') ?: 'app\\' . $appName);
        if (is_dir($appPath)) {
            $this->app->setRuntimePath($this->app->getRuntimePath() . $appName . DIRECTORY_SEPARATOR);
            $this->app->http->setRoutePath($this->getRoutePath());
            $this->loadApp($appPath);
        }
    }

    /**
     * 加载应用文件
     * @param string $appPath
     * @return void
     */
    protected function loadApp(string $appPath): void
    {
        if (is_file($appPath . 'common.php')) \Composer\Autoload\includeFile($appPath . 'common.php');
        foreach (glob($appPath . 'config' . DIRECTORY_SEPARATOR . '*' . $this->app->getConfigExt()) as $file) {
            $this->app->config->load($file, pathinfo($file, PATHINFO_FILENAME));
        }
        if (is_file($appPath . 'event.php')) $this->app->loadEvent(include $appPath . 'event.php');
        if (is_file($appPath . 'provider.php')) $this->app->bind(include $appPath . 'provider.php');
        if (is_file($appPath . 'middleware.php')) $this->app->middleware->import(include $appPath . 'middleware.php', 'app');
        // 加载应用默认语言包
        $this->app->loadLangPack($this->app->lang->defaultLangSet());
    }
}