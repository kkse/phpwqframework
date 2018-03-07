<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/11/1
 * Time: 11:17
 */

namespace kkse\wqframework\dtwq\system;

use Com\Internal\DbCache;
use Org\Member\Visitor;
use Org\Base\Member;
use think\Loader;

/**
 * 前台站点相关处理类
 * Class WebSite
 * @package dtwq\system
 */
class AppSite
{
    /**
     * @var Visitor|null
     */
    protected $visitor;

    /**
     * @var Member
     */
    protected $member;

    /**
     * @var int
     */
    protected $uniacid;

    public function __construct()
    {
        $this->visitor = Visitor::getInstance();
        $this->member = $this->visitor->getMember();
        //$this->uniacid = get_gpc('i', C('WECHAT_MAIN_DEFAULT_ID', null, 0), 'intval');

    }

    public function getMmber()
    {
        return $this->member;
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
        global $_W, $_GPC;

        load()->initctrl();

        load()->model('app');
        load()->app('common');
        load()->app('template');
        require IA_ROOT . '/app/common/bootstrap.app.inc.php';
        exit;

        $acl = array(
            'home' => array(
                'default' => 'home',
            ),
            'mc' => array(
                'default' => 'home'
            )
        );

        if ($_W['setting']['copyright']['status'] == 1) {
            $_W['siteclose'] = true;
            message('抱歉，站点已关闭，关闭原因：' . $_W['setting']['copyright']['reason']);
        }

        $multiid = intval($_GPC['t']);
        if(empty($multiid)) {
            $multiid = intval($unisetting['default_site']);
            unset($setting);
        }

        $multi = pdo_fetch("SELECT * FROM ".tablename('site_multi')." WHERE id=:id AND uniacid=:uniacid", array(':id' => $multiid, ':uniacid' => $_W['uniacid']));
        $multi['site_info'] = @iunserializer($multi['site_info']);

        $styleid = !empty($_GPC['s']) ? intval($_GPC['s']) : intval($multi['styleid']);
        $style = pdo_fetch("SELECT * FROM ".tablename('site_styles')." WHERE id = :id", array(':id' => $styleid));

        $templates = uni_templates();
        $templateid = intval($style['templateid']);
        $template = $templates[$templateid];

        $_W['template'] = !empty($template) ? $template['name'] : 'default';
        $_W['styles'] = array();

        if(!empty($template) && !empty($style)) {
            $sql = "SELECT `variable`, `content` FROM " . tablename('site_styles_vars') . " WHERE `uniacid`=:uniacid AND `styleid`=:styleid";
            $params = array();
            $params[':uniacid'] = $_W['uniacid'];
            $params[':styleid'] = $styleid;
            $stylevars = pdo_fetchall($sql, $params);
            if(!empty($stylevars)) {
                foreach($stylevars as $row) {
                    if (strexists($row['variable'], 'img')) {
                        $row['content'] = tomedia($row['content']);
                    }
                    $_W['styles'][$row['variable']] = $row['content'];
                }
            }
            unset($stylevars, $row, $sql, $params);
        }

        $_W['page'] = array();
        $_W['page']['title'] = $multi['title'];
        if(is_array($multi['site_info'])) {
            $_W['page'] = array_merge($_W['page'], $multi['site_info']);
        }
        unset($multi, $styleid, $style, $templateid, $template, $templates);

        $controllers = array();
        $handle = opendir(IA_ROOT . '/app/source/');
        if(!empty($handle)) {
            while($dir = readdir($handle)) {
                if($dir != '.' && $dir != '..') {
                    $controllers[] = $dir;
                }
            }
        }
        if(!in_array($controller, $controllers)) {
            $controller = 'home';
        }

        $init = IA_ROOT . "/app/source/{$controller}/__init.php";
        if(is_file($init)) {
            require $init;;
        }
        $actions = array();
        $handle = opendir(IA_ROOT . '/app/source/' . $controller);
        if(!empty($handle)) {
            while($dir = readdir($handle)) {
                if($dir != '.' && $dir != '..' && strexists($dir, '.ctrl.php')) {
                    $dir = str_replace('.ctrl.php', '', $dir);
                    $actions[] = $dir;
                }
            }
        }

        if(empty($actions)) {
            $str = '';
            if(uni_is_multi_acid()) {
                $str = "&j={$_W['acid']}";
            }
            header("location: index.php?i={$_W['uniacid']}{$str}&c=home?refresh");
        }
        if(!in_array($action, $actions)) {
            $action = $acl[$controller]['default'];
        }
        if(!in_array($action, $actions)) {
            $action = $actions[0];
        }
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

    protected function setCoreAction(array $core_action)
    {
        global $_GPC,$controller, $action, $do;
        $_GPC['c'] = $controller= $core_action['controller'];
        $_GPC['a'] = $action= $core_action['action'];
        $_GPC['do'] = $do= $core_action['do'];
    }

    public function getCtrlFile()
    {
        global $_W, $GPC, $controller, $action, $do;

        $file = IA_ROOT . '/app/source/' . $controller . '/' . $action . '.ctrl.php';
        if (!is_file($file)) {
            $controller = 'home';
            $action = 'home';
            $do = '';
            $file = IA_ROOT . '/app/source/' . $controller . '/' . $action . '.ctrl.php';
        }

        return $file;
    }

    public function loadCtrl()
    {
        global $_W, $GPC, $controller, $action, $do;

        $file = IA_ROOT . '/web/source/' . $controller . '/' . $action . '.ctrl.php';
        if (!is_file($file)) {
            $controller = 'home';
            $action = 'welcome';
            $do = '';
            $file = IA_ROOT . '/web/source/' . $controller . '/' . $action . '.ctrl.php';
        }

        require($file);
    }


    public function runCtrl()
    {
        global $controller, $action, $do;
        $class_name = "\\web\\{$controller}\\".Loader::parseName($action, 1);
        if (class_exists($class_name, false)) {
            $c = new $class_name();
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