<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/3/1
 * Time: 11:17
 */

namespace kkse\wqframework\base;


abstract class WeAccount {

    const TYPE_WEIXIN = '1';
    const TYPE_YIXIN = '2';
    const TYPE_WEIXIN_PLATFORM = '3';


    public static function create($acidOrAccount = '') {
        global $_W;
        if(empty($acidOrAccount)) {
            $acidOrAccount = $_W['account'];
        }
        if (is_array($acidOrAccount)) {
            $account = $acidOrAccount;
        } else {
            $account = account_fetch($acidOrAccount);
        }
        if (is_error($account)) {
            $account = $_W['account'];
        }
        if(!empty($account) && isset($account['type'])) {
            if($account['type'] == self::TYPE_WEIXIN) {
                load()->classs('weixin.account');
                return new WeiXinAccount($account);
            }
            if($account['type'] == self::TYPE_YIXIN) {
                load()->classs('yixin.account');
                return new YiXinAccount($account);
            }
            if($account['type'] == self::TYPE_WEIXIN_PLATFORM) {
                load()->classs('weixin.platform');
                return new WeiXinPlatform($account);
            }
        }
        return null;
    }

    static public function token($type = 1) {
        $classname = self::includes($type);
        $obj = new $classname();
        return $obj->fetch_available_token();
    }

    static public function includes($type = 1) {
        if($type == '1') {
            load()->classs('weixin.account');
            return 'WeiXinAccount';
        }
        if($type == '2') {
            load()->classs('yixin.account');
            return 'YiXinAccount';
        }
    }


    abstract public function __construct($account);


    public function checkSign() {
        trigger_error('not supported.', E_USER_WARNING);
    }


    public function fetchAccountInfo() {
        trigger_error('not supported.', E_USER_WARNING);
    }


    public function queryAvailableMessages() {
        return array();
    }


    public function queryAvailablePackets() {
        return array();
    }


    public function parse($message) {
        global $_W;
        if (!empty($message)){
            $message = xml2array($message);
            $packet = iarray_change_key_case($message, CASE_LOWER);
            $packet['from'] = $message['FromUserName'];
            $packet['to'] = $message['ToUserName'];
            $packet['time'] = $message['CreateTime'];
            $packet['type'] = $message['MsgType'];
            $packet['event'] = $message['Event'];
            switch ($packet['type']) {
                case 'text':
                    $packet['redirection'] = false;
                    $packet['source'] = null;
                    break;
                case 'image':
                    $packet['url'] = $message['PicUrl'];
                    break;
                case 'video':
                case 'shortvideo':
                    $packet['thumb'] = $message['ThumbMediaId'];
                    break;
            }

            switch ($packet['event']) {
                case 'subscribe':
                    $packet['type'] = 'subscribe';
                case 'SCAN':
                    if ($packet['event'] == 'SCAN') {
                        $packet['type'] = 'qr';
                    }
                    if(!empty($packet['eventkey'])) {
                        $packet['scene'] = str_replace('qrscene_', '', $packet['eventkey']);
                        if(strexists($packet['scene'], '\u')) {
                            $packet['scene'] = '"' . str_replace('\\u', '\u', $packet['scene']) . '"';
                            $packet['scene'] = json_decode($packet['scene']);
                        }

                    }
                    break;
                case 'unsubscribe':
                    $packet['type'] = 'unsubscribe';
                    break;
                case 'LOCATION':
                    $packet['type'] = 'trace';
                    $packet['location_x'] = $message['Latitude'];
                    $packet['location_y'] = $message['Longitude'];
                    break;
                case 'pic_photo_or_album':
                case 'pic_weixin':
                case 'pic_sysphoto':
                    $packet['sendpicsinfo']['piclist'] = array();
                    $packet['sendpicsinfo']['count'] = $message['SendPicsInfo']['Count'];
                    if (!empty($message['SendPicsInfo']['PicList'])) {
                        foreach ($message['SendPicsInfo']['PicList']['item'] as $item) {
                            if (empty($item)) {
                                continue;
                            }
                            $packet['sendpicsinfo']['piclist'][] = is_array($item) ? $item['PicMd5Sum'] : $item;
                        }
                    }
                    break;
                case 'card_pass_check':
                case 'card_not_pass_check':
                case 'user_get_card':
                case 'user_del_card':
                case 'user_consume_card':
                case 'poi_check_notify':
                    $packet['type'] = 'coupon';
                    break;
            }
        }
        return $packet;
    }


    public function response($packet) {
        if (is_error($packet)) {
            return '';
        }
        if (!is_array($packet)) {
            return $packet;
        }
        if(empty($packet['CreateTime'])) {
            $packet['CreateTime'] = TIMESTAMP;
        }
        if(empty($packet['MsgType'])) {
            $packet['MsgType'] = 'text';
        }
        if(empty($packet['FuncFlag'])) {
            $packet['FuncFlag'] = 0;
        } else {
            $packet['FuncFlag'] = 1;
        }
        return array2xml($packet);
    }


    public function isPushSupported() {
        return false;
    }


    public function push($uniid, $packet) {
        trigger_error('not supported.', E_USER_WARNING);
    }


    public function isBroadcastSupported() {
        return false;
    }


    public function broadcast($packet, $targets = array()) {
        trigger_error('not supported.', E_USER_WARNING);
    }


    public function isMenuSupported() {
        return false;
    }


    public function menuCreate($menu) {
        trigger_error('not supported.', E_USER_WARNING);
    }


    public function menuDelete() {
        trigger_error('not supported.', E_USER_WARNING);
    }


    public function menuModify($menu) {
        trigger_error('not supported.', E_USER_WARNING);
    }


    public function menuQuery() {
        trigger_error('not supported.', E_USER_WARNING);
    }


    public function queryFansActions() {
        return array();
    }


    public function fansGroupAll() {
        trigger_error('not supported.', E_USER_WARNING);
    }


    public function fansGroupCreate($group) {
        trigger_error('not supported.', E_USER_WARNING);
    }


    public function fansGroupModify($group) {
        trigger_error('not supported.', E_USER_WARNING);
    }


    public function fansMoveGroup($uniid, $group) {
        trigger_error('not supported.', E_USER_WARNING);
    }


    public function fansQueryGroup($uniid) {
        trigger_error('not supported.', E_USER_WARNING);
    }


    public function fansQueryInfo($uniid, $isPlatform) {
        trigger_error('not supported.', E_USER_WARNING);
    }


    public function fansAll() {
        trigger_error('not supported.', E_USER_WARNING);
    }


    public function queryTraceActions() {
        return array();
    }


    public function traceCurrent($uniid) {
        trigger_error('not supported.', E_USER_WARNING);
    }


    public function traceHistory($uniid, $time) {
        trigger_error('not supported.', E_USER_WARNING);
    }


    public function queryBarCodeActions() {
        return array();
    }


    public function barCodeCreateDisposable($barcode) {
        trigger_error('not supported.', E_USER_WARNING);
    }


    public function barCodeCreateFixed($barcode) {
        trigger_error('not supported.', E_USER_WARNING);
    }

    public function downloadMedia($media){
        trigger_error('not supported.', E_USER_WARNING);
    }
}