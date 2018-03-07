<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/3/1
 * Time: 11:18
 */

namespace kkse\wqframework\base;


abstract class WeBase {

    public $modulename;

    public $module;

    public $weid;

    public $uniacid;

    public $__define;


    public function saveSettings($settings) {
        global $_W;
        $pars = array('module' => $this->modulename, 'uniacid' => $_W['uniacid']);
        $row = array();
        $row['settings'] = iserializer($settings);
        cache_build_account_modules();
        if (pdo_fetchcolumn("SELECT module FROM ".tablename('uni_account_modules')." WHERE module = :module AND uniacid = :uniacid", array(':module' => $this->modulename, ':uniacid' => $_W['uniacid']))) {
            return pdo_update('uni_account_modules', $row, $pars) !== false;
        } else {
            return pdo_insert('uni_account_modules', array('settings' => iserializer($settings), 'module' => $this->modulename ,'uniacid' => $_W['uniacid'], 'enabled' => 1)) !== false;
        }
    }


    protected function createMobileUrl($do, $query = array(), $noredirect = true) {
        global $_W;
        $query['do'] = $do;
        $query['m'] = strtolower($this->modulename);
        return murl('entry', $query, $noredirect);
    }


    protected function createWebUrl($do, $query = array()) {
        $query['do'] = $do;
        $query['m'] = strtolower($this->modulename);
        return wurl('module/site_entry', $query);
        //return wurl('site/entry', $query);
    }


    protected function template($filename) {
        global $_W;
        $name = strtolower($this->modulename);
        $defineDir = dirname($this->__define);
        if(defined('IN_MP')) {
            $source = IA_ROOT . "/mp/themes/{$_W['template']}/{$name}/{$filename}.html";
            $compile = IA_ROOT . "/data/tpl/mp/{$_W['template']}/{$name}/{$filename}.tpl.php";
            if(!is_file($source)) {
                $source = IA_ROOT . "/mp/themes/default/{$name}/{$filename}.html";
            }
            if(!is_file($source)) {
                $source = $defineDir . "/template/{$filename}.html";
            }
            if(!is_file($source)) {
                $source = IA_ROOT . "/mp/themes/{$_W['template']}/{$filename}.html";
            }
            if(!is_file($source)) {
                $source = IA_ROOT . "/mp/themes/default/{$filename}.html";
            }
        }
        elseif (defined('IN_SYS')) {
            $source = IA_ROOT . "/web/themes/{$_W['template']}/{$name}/{$filename}.html";
            $compile = IA_ROOT . "/data/tpl/web/{$_W['template']}/{$name}/{$filename}.tpl.php";
            if(!is_file($source)) {
                $source = IA_ROOT . "/web/themes/default/{$name}/{$filename}.html";
            }
            if(!is_file($source)) {
                $source = $defineDir . "/template/{$filename}.html";
            }
            if(!is_file($source)) {
                $source = IA_ROOT . "/web/themes/{$_W['template']}/{$filename}.html";
            }
            if(!is_file($source)) {
                $source = IA_ROOT . "/web/themes/default/{$filename}.html";
            }
        }
        else {
            $source = IA_ROOT . "/app/themes/{$_W['template']}/{$name}/{$filename}.html";
            $compile = IA_ROOT . "/data/tpl/app/{$_W['template']}/{$name}/{$filename}.tpl.php";
            if(!is_file($source)) {
                $source = IA_ROOT . "/app/themes/default/{$name}/{$filename}.html";
            }
            if(!is_file($source)) {
                $source = $defineDir . "/template/mobile/{$filename}.html";
            }
            if(!is_file($source)) {
                $source = IA_ROOT . "/app/themes/{$_W['template']}/{$filename}.html";
            }
            if(!is_file($source)) {
                if (in_array($filename, array('header', 'footer', 'footer-base', 'slide', 'toolbar', 'message'))) {
                    $source = IA_ROOT . "/app/themes/default/common/{$filename}.html";
                } else {
                    $source = IA_ROOT . "/app/themes/default/{$filename}.html";
                }
            }
        }
        if(!is_file($source)) {
            exit("Error: template source '{$filename}' is not exist!");
        }
        $paths = pathinfo($compile);
        $compile = str_replace($paths['filename'], $_W['uniacid'] . '_' . $paths['filename'], $compile);
        if (DEVELOPMENT || !is_file($compile) || filemtime($source) > filemtime($compile)) {
            template_compile($source, $compile, true);
        }
        return $compile;
    }
    protected function pctemplate($filename) {
        global $_W;
        $name = strtolower($this->modulename);
        $defineDir = dirname($this->__define);
        if(defined('IN_MP')) {
            $source = IA_ROOT . "/mp/themes/{$_W['template']}/{$name}/{$filename}.html";
            $compile = IA_ROOT . "/data/tpl/mp/{$_W['template']}/{$name}/{$filename}.tpl.php";
            if(!is_file($source)) {
                $source = IA_ROOT . "/mp/themes/default/{$name}/{$filename}.html";
            }
            if(!is_file($source)) {
                $source = $defineDir . "/template/{$filename}.html";
            }
            if(!is_file($source)) {
                $source = IA_ROOT . "/mp/themes/{$_W['template']}/{$filename}.html";
            }
            if(!is_file($source)) {
                $source = IA_ROOT . "/mp/themes/default/{$filename}.html";
            }
        }
        elseif(defined('IN_SYS')) {
            $source = IA_ROOT . "/web/themes/{$_W['template']}/{$name}/{$filename}.html";
            $compile = IA_ROOT . "/data/tpl/web/{$_W['template']}/{$name}/{$filename}.tpl.php";
            if(!is_file($source)) {
                $source = IA_ROOT . "/web/themes/default/{$name}/{$filename}.html";
            }
            if(!is_file($source)) {
                $source = $defineDir . "/template/{$filename}.html";
            }
            if(!is_file($source)) {
                $source = IA_ROOT . "/web/themes/{$_W['template']}/{$filename}.html";
            }
            if(!is_file($source)) {
                $source = IA_ROOT . "/web/themes/default/{$filename}.html";
            }
        }
        else {
            $source = IA_ROOT . "/app/pc/{$_W['template']}/{$name}/{$filename}.html";
            $compile = IA_ROOT . "/data/tpl/app/pc/{$_W['template']}/{$name}/{$filename}.tpl.php";
            if(!is_file($source)) {
                $source = IA_ROOT . "/app/pc/default/{$name}/{$filename}.html";
            }
            if(!is_file($source)) {
                $source = $defineDir . "/template/mobile/{$filename}.html";
            }
            if(!is_file($source)) {
                $source = IA_ROOT . "/app/pc/{$_W['template']}/{$filename}.html";
            }
            if(!is_file($source)) {
                if (in_array($filename, array('header', 'footer', 'footer-base', 'slide', 'toolbar', 'message'))) {
                    $source = IA_ROOT . "/app/pc/default/common/{$filename}.html";
                } else {
                    $source = IA_ROOT . "/app/pc/default/{$filename}.html";
                }
            }
        }
        if(!is_file($source)) {
            exit("Error: template source '{$filename}' is not exist!");
        }
        $paths = pathinfo($compile);
        $compile = str_replace($paths['filename'], $_W['uniacid'] . '_' . $paths['filename'], $compile);
        if (DEVELOPMENT || !is_file($compile) || filemtime($source) > filemtime($compile)) {
            template_compile($source, $compile, true);
        }
        return $compile;
    }
}