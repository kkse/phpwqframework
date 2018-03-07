<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/11/1
 * Time: 11:17
 */

namespace kkse\wqframework\dtwq\system;

use Com\Internal\DbCache;
use kkse\wqframework\dtwq\admin\Ascription;
use kkse\wqframework\dtwq\admin\User;
use kkse\wqframework\dtwq\admin\UserRole;
use kkse\wqframework\dtwq\admin\Visitor;
use kkse\wqframework\dtwq\examine\Author;
use think\Loader;
use kkse\wqframework\dtwq\base\CoreAction;

/**
 * 后台站点相关处理类
 * Class WebSite
 * @package dtwq\system
 */
class WebSite
{
    /**
     * @var Visitor|null
     */
    protected $visitor;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var int
     */
    protected $uniacid;

    protected $sm;

    protected $ascription;

    /**
     * @var CoreAction
     */
    protected $ca;


    public function __construct()
    {
        $this->visitor = Visitor::getInstance();
        $this->user = $this->visitor->getUser();

        if ($this->user) {
            $this->uniacid = intval(session('uniacid'));
            $this->ascription = new Ascription($this->user);
        }

        $this->sm = new SystemMenu('web');
    }

    public function getUser()
    {
        return $this->user;
    }

    public function getSystemMenu()
    {
        return $this->sm;
    }

    public function getCurrentCoreAction()
    {
        if (!$this->ca) {
            $ca = CoreAction::currentCoreAction('web')
            or message('访问地址异常。', '', 'error');
            $this->ca = $ca->getFinalCoreAction();
        }
        return $this->ca;
    }

    public function getAscription()
    {
        return $this->ascription;
    }

    public function getAscriptionInfo()
    {
        if ($this->ascription) {
            return $this->ascription->getInfos();
        }
        return [];
    }

    public function getUserRole()
    {
        if ($this->user) {
            return UserRole::getUserRole($this->user);
        }
        return null;
    }

    public function getCurrentAuthor()
    {
        if ($this->user) {
            return new Author($this->user);
        }
        return null;
    }

    /**
     * 获取logo
     * @return bool|mixed|string
     */
    public function getLogoThumb()
    {
        global $_W;

        //如果已经登录
        if ($this->user) {
            $thumb = '';
            if ($this->user->isOrg()) {
                $this->user->getOrgcode();
                $thumb = DbCache::data_getcolumn('dt_org', $this->user->getOrgcode(), 'thumb');
            } elseif($this->user->isNet()) {
                $thumb = DbCache::data_getcolumn('dt_bank_net', $this->user->getNetcode(), 'thumb');
            }
            if ($thumb) {
                return $thumb;
            }
        }

         if (!empty($_W['setting']['copyright']['blogo'])) {
            return tomedia($_W['setting']['copyright']['blogo']);
        }

        return '';
    }

    public function getMenuInfo()
    {
        $menu_key = '';
        $menu_time = '';
        if ($this->user && $menu_id = $this->user->getMenuId()) {
            $menuInfo = $this->sm->getMenuInfo($menu_id);
            $menu_time = $menuInfo['uptime'];
            $menu_key = sprintf('menu:%s-%s-%s', $this->user->getUid(), $this->user->getRoleid(), $menu_id);
        }
        return [
            'menu_key'=>$menu_key,
            'menu_time'=>$menu_time,
        ];
    }

    public static function run()
    {
        global $_W;
        $_W['site'] = new self();
        $_W['site']->_run();
        return $_W['site'];
    }

    /**
     * @return self
     */
    public static function getRuning()
    {
        global $_W;
        if (!empty($_W['site']) && $_W['site'] instanceof self) {
            return $_W['site'];
        }

        return null;
    }

    protected function _run()
    {
        $this->setUser();//设置登录的用户信息

        $this->checkPermission();//进行权限总控
        $this->checkFilter();//过滤白名单

        $this->setUniacid();//设置选中的公众号

        //$sm = new SystemMenu('web');//在模板中调用
    }

    public function dataDecrypt($endata)
    {
        $privateKeyFilePath = CERT_PATH.'cipherPrv.key';
        $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyFilePath), 'qq02468qq');
        if (!$privateKey) {
            message('密钥不可用');
        }

        $dedata = '';
        if (openssl_private_decrypt(base64_decode($endata), $dedata, $privateKey)) {
            return $dedata;
        }

        return false;
    }

    public function checkVerifyCode($verify)
    {
        global $_W;
        if (!empty($_W['setting']['copyright']['verifycode'])) {
            $verify = trim($verify);
            if(empty($verify)) {
                return '';
            }
            return checkcaptcha($verify);
        }

        return true;
    }

    /**
     * 检查访问控制
     */
    public function checkPermission()
    {
        global $_W;
        $ca = $this->getCurrentCoreAction();
        $this->setCoreAction($ca);

        //只要是系统管理员就返回true
        if ($this->user && $this->user->isAdministrator()) {
            return true;
        }

        $_W['setting']['copyright']['status'] == 1 and $_W['siteclose'] = true;//站点关闭

        if ($ca->getControl() == 'public') {//表示就算关闭站点也能访问的，例如登录页之类的。
            return true;
        }

        if (!empty($_W['siteclose'])) {
            $this->visitor->logout();
            message('站点已关闭，关闭原因：'.$_W['setting']['copyright']['reason'],
                url('user/login'), 'info');
        }


        if ($ca->getControl() == 'open_public') {//表示需要站点打开后才能公开访问的
            return true;
        }

        if (!$this->user) {
            defined('IN_GW') or define('IN_GW', true);
            message('抱歉，您无权进行该操作，请先登录！', url('user/login'), 'warning');
        }

        if ($this->user->isBin()) {
            $this->visitor->logout();
            message('您的账号正在审核或是已经被系统禁止，请联系网站管理员解决！', url('user/login'), 'warning');
        }

        if (!$this->user->checkEffective()){
            message('该账号权限配置异常，需要重新配置，禁止访问！', url('user/login'), 'warning');
        }

        if ($ca->getControl() == 'login') {
            return true;
        }

        if ($ca->getControl() == 'admin') {
            message('您没有进行该操作的权限，只能系统管理员访问。', '', 'error');
        }

        if ($ca->getControl() != 'permission') {
            message('动作权限配置异常', '', 'error');
        }

        if (!UserRole::getUserRole($this->user)->testPermissionAction($ca, $why)) {
            message($why, '', 'error');
        }
        return true;
    }

    public function checkFilter()
    {
        global $_GPC,$controller, $action, $do;
        check_fail_filter($_GPC, '', get_filter_white_list('app', $controller, $action, $do));
        //$_GPC = ihtmlspecialchars($_GPC);
        check_referer();
    }

    protected function setUser()
    {
        global $_W;
        if ($this->user) {
            $_W['isfounder'] = $this->user->isAdministrator();
            $_W['uid'] = $this->user->getUid();
            $_W['username'] = $this->user->getUsername();
            $_W['user'] = $this->user->toArray();
        }
    }

    protected function setUniacid()
    {
        global $_W;

        if(!$this->uniacid) {
            $one = pdo_get('uni_account', [], [], '`rank` DESC')
            and $this->uniacid = intval($one['uniacid']);
            $this->uniacid and session('uniacid', $this->uniacid);
        }

        if ($this->uniacid) {
            $_W['uniacid'] = $this->uniacid;
            $_W['uniaccount'] = $_W['account'] = uni_fetch($_W['uniacid']);
            $_W['acid'] = $_W['account']['acid'];
            $_W['weid'] = $_W['uniacid'];
            //$this->user and $_W['role'] = uni_permission($this->user->getUid(), $_W['uniacid']);
        }
    }

    protected function setCoreAction(CoreAction $core_action)
    {
        global $_GPC,$controller, $action, $do, $module, $op;
        $_GPC['c'] = $controller = $core_action->getController();
        $_GPC['a'] = $action = $core_action->getAction();
        $_GPC['do'] = $do = $core_action->getDo();
        if ($core_action->isModule()) {
            $_GPC['m'] = $module = $core_action->getModule();
            $_GPC['op'] = $op = $core_action->getOp();
        }
    }

    public function getCtrlFile()
    {
        global $controller, $action;

        $file = IA_ROOT . '/web/source/' . $controller . '/' . $action . '.ctrl.php';
        if (!is_file($file)) {
            message('无效的访问地址。', '', 'error');
        }

        return $file;
    }

    /*public function loadCtrl()
    {
        global $_W, $GPC;
        require($this->getCtrlFile());
    }*/


    public function runCtrl()
    {
        global $controller, $action, $do, $_W;
        $class_name = "\\web\\{$controller}\\".Loader::parseName($action, 1);
        if (class_exists($class_name, false)) {
            $c = new $class_name();
            $ca = $this->getCurrentCoreAction();
            if ($ca->isModule()) {
                if ($c instanceof ModuleCtrl || method_exists($c, 'run')) {
                    $c->run();
                }
            } else {
                (empty($do) || !method_exists($c, $do)) && property_exists($c, 'default_func')
                and $do = $c->default_func;

                property_exists($c, 'page_title') and $_W['page']['title'] = $c->page_title;

                method_exists($c, $do) and $c->$do();

                //由这里加载模板
                if (property_exists($c, 'load_template') && $c->load_template) {
                    $load_template = $c->load_template;
                    $load_template === true and $load_template = get_default_tpl();
                    $load_template === ':default:' and $load_template = "comtpl/{$do}";

                    template($load_template);
                }
            }
        }
    }



    //检查当前的菜单是否包含指定的动作
    protected function hasMenu($controller, $action)
    {
        $menus = [];
        if ($menus) {
            foreach ($menus as $i => $nav) {
                foreach ($nav['menus'] as $j => $menu) {
                    foreach ($menu['menus'] as $k => $item) {
                        parse_str(parse_url($item['url'], PHP_URL_QUERY), $query);
                        $c = empty($query['c'])?'':$query['c'];
                        $a = empty($query['a'])?'':$query['a'];
                        if ($controller == $c && $action == $a) {
                            $menus[$i]['selected'] = true;
                            $menus[$i]['menus'][$j]['selected'] = true;
                            $menus[$i]['menus'][$j]['menus'][$k]['selected'] = true;
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function checkAction($segment)
    {
        if (!$this->user) {
            return false;
        }

        list($controller, $action, $do) = explode('/', $segment . '//');
        empty($controller) and $controller = $GLOBALS['controller'];
        empty($action) and $action = $GLOBALS['action'];

        $ca = CoreAction::getCoreAction('web', $controller, $action, $do);
        $ur = $this->getUserRole();
        return $ur->testPermissionVisit($ca);
    }

    public function checkModule($segment)
    {
        if (!$this->user) {
            return false;
        }

        list($module, $action, $do, $op) = explode('/', $segment . '///');
        empty($module) and $module = $GLOBALS['module'];
        empty($action) and $action = $GLOBALS['action'];
        if (!$module) {
            return false;//未知模块
        }

        $ca = CoreAction::getModuleAction('web', $module, $action, $do, $op);
        $ur = $this->getUserRole();
        return $ur->testPermissionVisit($ca);
    }

    public function autoCheckAction(array $actions) {
        $arr = [];
        foreach ($actions as $url=>$title) {
            if (!$this->checkAction($url)) {
                continue;
            }
            if (is_string($title)) {
                $item = explode(':', $title, 4);
                $title = $item[0];
                $pk_field = empty($item[1])?'id':$item[1];
                $key_field = empty($item[2])?$pk_field:$item[2];
                $info = ['title'=>$title,'url'=>$url,'key_field'=>$key_field,'pk_field'=>$pk_field];
                empty($item[3]) or $info['tip'] = $item[3];

                $arr[] = $info;
            } elseif (is_array($title)) {
                $title['url'] = $url;
                $arr[] = $title;
            }
        }

        return $arr;
    }

    public function genSearch(array $items = [])
    {
        global $controller, $action, $do;

        $search = new Search();
        $search->addHiddenItems([
            'c'=>$controller,
            'a'=>$action,
            'd'=>$do,
        ]);
        $search->addItems($items);
        return $search;
    }
}