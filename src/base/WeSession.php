<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/3/1
 * Time: 16:17
 */

namespace kkse\wqframework\base;


class WeSession implements \SessionHandlerInterface{

    public static $uniacid;

    public static $openid;

    public static $expire;


    public static function start($uniacid, $openid, $expire = 3600) {
        self::$uniacid = $uniacid;
        self::$openid = $openid;
        self::$expire = $expire;
        $sess = new self();
        session_set_save_handler($sess, true);
        session_start();
    }

    public function open($save_path, $name) {
        return true;
    }

    public function close() {
        return true;
    }


    public function read($sessionid) {
        $row = pdo_get('core_sessions', ['sid'=>$sessionid, 'expiretime'=>['gt', TIMESTAMP]]);
        if(is_array($row) && !empty($row['data'])) {
            return $row['data'];
        }
        return '';
    }


    public function write($sessionid, $data) {
        $row = array();
        $row['sid'] = $sessionid;
        $row['uniacid'] = self::$uniacid;
        $row['openid'] = self::$openid;
        $row['data'] = $data;
        $row['expiretime'] = TIMESTAMP + self::$expire;
        pdo_insert('core_sessions', $row, true);
        return true;
    }


    public function destroy($sessionid) {
        $row = array();
        $row['sid'] = $sessionid;

        return pdo_delete('core_sessions', $row) == 1;
    }


    public function gc($expire) {
        return pdo_delete('core_sessions', ['expiretime'=>['lt', TIMESTAMP]]);
    }
}