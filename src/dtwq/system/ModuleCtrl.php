<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/1/23
 * Time: 23:21
 */

namespace kkse\wqframework\dtwq\system;


abstract class ModuleCtrl extends BaseCtrl
{
    abstract public function run();

    protected function _getModuleEntry()
    {
        //需要指定m
        global $_GPC;
        if(empty($_GPC['m']) || empty($_GPC['do'])) {
            message('非法访问.');
        }
        $entry = pdo_get('modules_bindings', ['module'=>$_GPC['m'], 'do'=>$_GPC['do']]);
        if (empty($entry)) {
            $entry = array(
                'module' => $_GPC['m'],
                'do' => $_GPC['do'],
                'state' => $_GPC['state'],
                'direct' => $_GPC['direct']
            );
        }

        if(empty($entry['direct'])) {
            load()->model('module');
            $module = module_fetch($entry['module']);
            if(empty($module)) {
                message("访问非法, 没有操作权限. (module: {$entry['module']})");
            }
            if($entry['entry'] == 'menu') {
                $permission = uni_user_module_permission_check($entry['module'] . '_menu_' . $entry['do'], $entry['module']);
            } else {
                $permission = uni_user_module_permission_check($entry['module'] . '_rule', $entry['module']);
            }
            if(!$permission) {
                message('您没有权限进行该操作');
            }
            define('FRAME', 'ext');
            define('CRUMBS_NAV', 1);
            $ptr_title = $entry['title'];
            $module_types = module_types();
            if($_COOKIE['ext_type'] == 1) {
                define('ACTIVE_FRAME_URL', url('site/entry/', array('eid' => $entry['eid'])));
            } else {
                define('ACTIVE_FRAME_URL', url('home/welcome/ext', array('m' => $entry['module'])));
            }
            /* $frames = buildframes(array(FRAME));
            $frames = $frames[FRAME]; */
        }


        $_GPC['__entry'] = $entry['title'];
        $_GPC['__state'] = $entry['state'];

        if(!empty($_W['modules'][$entry['module']]['handles']) && (count($_W['modules'][$entry['module']]['handles']) > 1 || !in_array('text', $_W['modules'][$entry['module']]['handles']))) {
            $handlestips = true;
        }
        $modules = uni_modules();
        $_W['current_module'] = $modules[$entry['module']];

        return $entry;
    }
}