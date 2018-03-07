<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/3/1
 * Time: 11:19
 */

namespace kkse\wqframework\base;


abstract class WeModuleCron extends WeBase {
    public function __call($name, $arguments) {
        if($this->modulename == 'task') {
            $dir = WQ_ROOT . '/framework/builtin/task/cron/';
        } else {
            $dir = IA_ROOT . '/addons/' . $this->modulename . '/cron/';
        }
        $fun = strtolower(substr($name, 6));
        $file = $dir . $fun . '.inc.php';
        if(file_exists($file)) {
            require $file;
            exit;
        }
        trigger_error("访问的方法 {$name} 不存在.", E_USER_WARNING);
        return error(-1009, "访问的方法 {$name} 不存在.");
    }

    public function addCronLog($tid, $errno, $note, $tag = array()) {
        global $_W;
        if(!$tid) {
            message(error(-1, 'tid参数错误'), '', 'ajax');
        }
        $data = array(
            'uniacid' => $_W['uniacid'],
            'module' => $this->modulename,
            'type' => $_W['cron']['filename'],
            'tid' => $tid,
            'note' => $note,
            'tag' => iserializer($tag),
            'createtime' => TIMESTAMP
        );
        pdo_insert('core_cron_record', $data);
        message(error($errno, $note), '', 'ajax');
    }
}