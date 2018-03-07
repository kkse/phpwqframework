<?php
namespace kkse\wqframework\base;

use lang\Agent;
use think\Config;
use think\Request;

class Loader
{
    private $cache = array();

    public function func($name)
    {
        if (isset($this->cache['func'][$name])) {
            return true;
        }
        $file = WQ_ROOT . '/function/' . $name . '.func.php';
        if (file_exists($file)) {
            include $file;
            $this->cache['func'][$name] = true;
            return true;
        } else {
            trigger_error('Invalid Helper Function /framework/function/' . $name . '.func.php', E_USER_ERROR);
            return false;
        }
    }

    public function model($name)
    {
        if (isset($this->cache['model'][$name])) {
            return true;
        }
        $file = WQ_ROOT . '/model/' . $name . '.mod.php';
        if (file_exists($file)) {
            include $file;
            $this->cache['model'][$name] = true;
            return true;
        } else {
            trigger_error('Invalid Model /framework/model/' . $name . '.mod.php', E_USER_ERROR);
            return false;
        }
    }

    //初始化微擎管理结构
    public function initctrl()
    {
        global $_W;
        is_array($_W) or $_W = [];
        $_W['config'] = ['upload' => Config::get('upload')];
        $_W['timestamp'] = TIMESTAMP;
        $_W['clientip'] = CLIENT_IP;
        $_W['ishttps'] = Request::instance()->isSsl();
        $_W['isajax'] = Request::instance()->isAjax();
        $_W['ispost'] = Request::instance()->isPost();
        $_W['sitescheme'] = $_W['ishttps'] ? 'https://' : 'http://';
        $_W['os'] = Agent::getDeviceTypeName(Agent::deviceType());
        $_W['is_wechat'] = Agent::isMicroMessage();
        $_W['is_alipay'] = Agent::isAlipay();
        $_W['template'] = 'default';


        $this->func('global');
        $this->func('quick');
        $this->func('template');

        $this->model('cache');
        $this->model('account');
        $this->model('setting');
        $this->func('filter');
        $this->func('business');

        $this->init_gpc();//初始化gpc数组
        $this->init_ajax();//转义ajax请求
        $this->init_server();//初始化服务器参数

        $_W['attachurl'] = $_W['attachurl_local'] = $_W['siteroot'] . $_W['config']['upload']['attachdir'] . '/';
        $_W['attachurl_remote'] = '';


        $this->init_cado();//初始化c、a、do

        setting_load();
        $this->initjssysinfo();
        $this->func('tpl');
    }

    protected function init_cado()
    {
        global $_GPC, $module, $controller, $action, $do;

        $module = '';//模块
        $controller = '';//控制目录
        $action = '';//文件
        $do = '';//动作

        if (!empty($_GPC['c'])) {
            $_GPC['c'] = preg_replace("/[^a-zA-Z0-9_\\-]/", '', $_GPC['c']);
        }

        if (!empty($_GPC['a'])) {
            $_GPC['a'] = preg_replace("/[^a-zA-Z0-9_\\-]/", '', $_GPC['a']);
        }

        if (!empty($_GPC['do'])) {
            $_GPC['do'] = preg_replace("/[^a-zA-Z0-9_\\-]/", '', $_GPC['do']);
        }

        !empty($_GPC['c']) and $controller = $_GPC['c'];
        !empty($_GPC['a']) and $action = $_GPC['a'];
        !empty($_GPC['do']) and $do = $_GPC['do'];

        $_GPC['c'] = $controller;
        $_GPC['a'] = $action;
        $_GPC['do'] = $do;
    }

    protected function init_gpc()
    {
        global $_GPC;
        is_array($_GPC) or $_GPC = [];
        $cprefix = Config::get('cookie.prefix');
        $cplen = strlen($cprefix);
        foreach ($_COOKIE as $key => $value) {
            if (substr($key, 0, $cplen) == $cprefix) {
                $_GPC[substr($key, $cplen)] = $value;
            }
        }

        $_GPC = array_merge($_GET, $_POST, $_GPC);
        $_GPC = ihtmlspecialchars($_GPC);
        $_GPC = ifilter($_GPC);
    }

    protected function init_ajax()
    {
        global $_W,$_GPC;
        if ($_W['ispost'] && !$_POST) {//是post提交，但是没有解析为post数组，可能是json数据
            $input = file_get_contents("php://input");
            if (!empty($input)) {
                $__input = @json_decode($input, true);
                if (!empty($__input)) {
                    $_GPC['__input'] = $__input;
                    $_W['isajax'] = true;
                }
            }
        }
    }

    protected function init_server()
    {
        global $_W;
        $_W['script_name'] = htmlspecialchars(scriptname());
        $sitepath = substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'));
        $_W['siteroot'] = htmlspecialchars($_W['sitescheme'] . $_SERVER['HTTP_HOST'] . $sitepath);
        $_W['http_host'] = htmlspecialchars($_W['sitescheme'] . $_SERVER['HTTP_HOST'] . '/');

        if (substr($_W['siteroot'], -1) != '/') {
            $_W['siteroot'] .= '/';
        }

        $urls = parse_url($_W['siteroot']);
        $urls['path'] = str_replace(array('/web', '/app', '/api'), '', $urls['path']);
        $_W['siteroot'] = $urls['scheme'] . '://' . $urls['host'] . ((!empty($urls['port']) && $urls['port'] != '80') ? ':' . $urls['port'] : '') . $urls['path'];
        $_W['siteurl'] = $urls['scheme'] . '://' . $urls['host'] . ((!empty($urls['port']) && $urls['port'] != '80') ? ':' . $urls['port'] : '') . $_W['script_name'] . (empty($_SERVER['QUERY_STRING']) ? '' : '?') . $_SERVER['QUERY_STRING'];
    }

    protected function initjssysinfo()
    {
        global $_W;
        $jssysinfo = [
            'siteroot'=>$_W['siteroot'],
            'attachurl'=>$_W['attachurl'],
            'attachurl_local'=>$_W['attachurl_local'],
            'attachurl_remote'=>$_W['attachurl_remote'],
        ];

        !empty($_W['uniacid']) and $jssysinfo['uniacid'] = $_W['uniacid'];
        !empty($_W['acid']) and $jssysinfo['acid'] = $_W['acid'];
        !empty($_W['openid']) and $jssysinfo['openid'] = $_W['openid'];
        !empty($_W['uid']) and $jssysinfo['uid'] = $_W['uid'];
        !empty($_W['MODULE_URL']) and $jssysinfo['MODULE_URL'] = $_W['MODULE_URL'];

        $_W['jssysinfo'] = $jssysinfo;
    }

    public static function load() {
        static $loader;
        if (empty($loader)) {
            $loader = new Loader();
        }
        return $loader;
    }
}
