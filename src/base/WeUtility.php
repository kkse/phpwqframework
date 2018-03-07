<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/3/1
 * Time: 11:18
 */

namespace kkse\wqframework\base;


class WeUtility {

    private static function defineConst($obj){
        global $_W;

        if ($obj instanceof WeBase) {

            if (!defined('MODULE_ROOT')) {
                define('MODULE_ROOT', dirname($obj->__define));
            }
            if (!defined('MODULE_URL')) {
                define('MODULE_URL', $_W['http_host'].'addons/'.$obj->modulename.'/');
            }


        }
    }


    public static function createModule($name) {
        global $_W;
        static $file;
        $classname = ucfirst($name) . 'Module';
        if(!class_exists($classname)) {
            $file = IA_ROOT . "/addons/{$name}/module.php";
            if(!is_file($file)) {
                $file = IA_ROOT . "/framework/builtin/{$name}/module.php";
            }
            if(!is_file($file)) {
                trigger_error('Module Definition File Not Found', E_USER_WARNING);
                return null;
            }
            require $file;
        }
        if(!class_exists($classname)) {
            trigger_error('Module Definition Class Not Found', E_USER_WARNING);
            return null;
        }
        $o = new $classname();
        $o->uniacid = $o->weid = $_W['uniacid'];
        $o->modulename = $name;
        load()->model('module');
        $o->module = module_fetch($name);
        $o->__define = $file;
        self::defineConst($o);
        if($o instanceof WeModule) {
            return $o;
        } else {
            trigger_error('Module Class Definition Error', E_USER_WARNING);
            return null;
        }
    }


    public static function createModuleProcessor($name) {
        global $_W;
        static $file;
        $classname = "{$name}ModuleProcessor";
        if(!class_exists($classname)) {
            $file = IA_ROOT . "/addons/{$name}/processor.php";
            if(!is_file($file)) {
                $file = IA_ROOT . "/framework/builtin/{$name}/processor.php";
            }
            if(!is_file($file)) {
                trigger_error('ModuleProcessor Definition File Not Found '.$file, E_USER_WARNING);
                return null;
            }
            require $file;
        }
        if(!class_exists($classname)) {
            trigger_error('ModuleProcessor Definition Class Not Found', E_USER_WARNING);
            return null;
        }
        $o = new $classname();
        $o->uniacid = $o->weid = $_W['uniacid'];
        $o->modulename = $name;
        load()->model('module');
        $o->module = module_fetch($name);
        $o->__define = $file;
        self::defineConst($o);
        if($o instanceof WeModuleProcessor) {
            return $o;
        } else {
            trigger_error('ModuleProcessor Class Definition Error', E_USER_WARNING);
            return null;
        }
    }


    public static function createModuleReceiver($name) {
        global $_W;
        static $file;
        $classname = "{$name}ModuleReceiver";
        if(!class_exists($classname)) {
            $file = IA_ROOT . "/addons/{$name}/receiver.php";
            if(!is_file($file)) {
                $file = IA_ROOT . "/framework/builtin/{$name}/receiver.php";
            }
            if(!is_file($file)) {
                trigger_error('ModuleReceiver Definition File Not Found '.$file, E_USER_WARNING);
                return null;
            }
            require $file;
        }
        if(!class_exists($classname)) {
            trigger_error('ModuleReceiver Definition Class Not Found', E_USER_WARNING);
            return null;
        }
        $o = new $classname();
        $o->uniacid = $o->weid = $_W['uniacid'];
        $o->modulename = $name;
        load()->model('module');
        $o->module = module_fetch($name);
        $o->__define = $file;
        self::defineConst($o);
        if($o instanceof WeModuleReceiver) {
            return $o;
        } else {
            trigger_error('ModuleReceiver Class Definition Error', E_USER_WARNING);
            return null;
        }
    }


    public static function createModuleSite($name) {
        global $_W;
        static $file;
        $classname = "{$name}ModuleSite";

        if(!class_exists($classname)) {
            $file = IA_ROOT . "/addons/{$name}/site.php";
            if(!is_file($file)) {

                $file = IA_ROOT . "/framework/builtin/{$name}/site.php";
            }
            if(!is_file($file)) {
                trigger_error('ModuleSite Definition File Not Found '.$file, E_USER_WARNING);
                return null;
            }
            require $file;
        }
        if(!class_exists($classname)) {
            trigger_error('ModuleSite Definition Class Not Found', E_USER_WARNING);
            return null;
        }
        $o = new $classname();

        $o->uniacid = $o->weid = $_W['uniacid'];
        $o->modulename = $name;
        load()->model('module');
        $o->module = module_fetch($name);
        $o->__define = $file;
        self::defineConst($o);
        $o->inMobile = defined('IN_MOBILE');
        if($o instanceof WeModuleSite) {
            return $o;
        } else {
            trigger_error('ModuleReceiver Class Definition Error', E_USER_WARNING);
            return null;
        }
    }


    public static function createModuleCron($name) {
        global $_W;
        static $file;
        $classname = "{$name}ModuleCron";
        if(!class_exists($classname)) {
            $file = IA_ROOT . "/addons/{$name}/cron.php";
            if(!is_file($file)) {
                $file = IA_ROOT . "/framework/builtin/{$name}/cron.php";
            }
            if(!is_file($file)) {
                trigger_error('ModuleCron Definition File Not Found '.$file, E_USER_WARNING);
                return error(-1006, 'ModuleCron Definition File Not Found');
            }
            require $file;
        }
        if(!class_exists($classname)) {
            trigger_error('ModuleCron Definition Class Not Found', E_USER_WARNING);
            return error(-1007, 'ModuleCron Definition Class Not Found');
        }
        $o = new $classname();
        $o->uniacid = $o->weid = $_W['uniacid'];
        $o->modulename = $name;
        load()->model('module');
        $o->module = module_fetch($name);
        $o->__define = $file;
        self::defineConst($o);
        if($o instanceof WeModuleCron) {
            return $o;
        } else {
            trigger_error('ModuleCron Class Definition Error', E_USER_WARNING);
            return error(-1008, 'ModuleCron Class Definition Error');
        }
    }


    public static function logging($level = 'info', $message = '') {
        $filename = IA_ROOT . '/data/logs/' . date('Ymd') . '.log';
        load()->func('file');
        mkdirs(dirname($filename));
        $content = date('Y-m-d H:i:s') . " {$level} :\n------------\n";
        if(is_string($message) && !in_array($message, array('post', 'get'))) {
            $content .= "String:\n{$message}\n";
        }
        if(is_array($message)) {
            $content .= "Array:\n";
            foreach($message as $key => $value) {
                $content .= sprintf("%s : %s ;\n", $key, $value);
            }
        }
        if($message === 'get') {
            $content .= "GET:\n";
            foreach($_GET as $key => $value) {
                $content .= sprintf("%s : %s ;\n", $key, $value);
            }
        }
        if($message === 'post') {
            $content .= "POST:\n";
            foreach($_POST as $key => $value) {
                $content .= sprintf("%s : %s ;\n", $key, $value);
            }
        }
        $content .= "\n";

        $fp = fopen($filename, 'a+');
        fwrite($fp, $content);
        fclose($fp);
    }
}