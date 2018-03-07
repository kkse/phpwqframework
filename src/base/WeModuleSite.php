<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/3/1
 * Time: 11:19
 */

namespace kkse\wqframework\base;

use Org\Member\Visitor;
use Org\Base\Factory as BaseFactory;

abstract class WeModuleSite extends WeBase {

    public $inMobile;

    public function __call($name, $arguments) {
        $isWeb = stripos($name, 'doWeb') === 0;
        $isMp = stripos($name, 'doMp') === 0;
        $isMobile = stripos($name, 'doMobile') === 0;
        if($isWeb || $isMp || $isMobile) {
            $fun = '';
            $dir = IA_ROOT . '/addons/' . $this->modulename . '/inc/';
            if($isWeb) {
                $dir .= 'web/';
                $fun = strtolower(substr($name, 5));
            }
            if($isMp) {
                $dir .= 'mp/';
                $fun = strtolower(substr($name, 4));
            }
            if($isMobile) {
                $dir .= 'mobile/';
                $fun = strtolower(substr($name, 8));
            }
            $file = $dir . $fun . '.inc.php';
            if(file_exists($file)) {
                require $file;
                exit;
            } else {
                $dir = str_replace("addons", "framework/builtin", $dir);
                $file = $dir . $fun . '.inc.php';
                if(file_exists($file)) {
                    require $file;
                    exit;
                } elseif ($isMp) {
                    $webfunc = 'doWeb'.$fun;
                    return $this->$webfunc();
                }
            }
        }
        trigger_error("访问的方法 {$name} 不存在.", E_USER_WARNING);
        return null;
    }


    protected function pay($params = array(), $mine = array()) {
        global $_W;
        $order_types = ['CF','CATERING'];  //CF众筹, CATERING微餐饮
        if ( !in_array($params['order_type'], $order_types)) {
            message('订单类型不符合！');
        }
        if(!$this->inMobile) {
            message('支付功能只能在手机上使用');
        }

        $mercode = $params['mercode'];
        $bus = BaseFactory::getMerchant($mercode);
        $businessList = $bus->getBusinessList();
        $mername = $bus->getMerName();

        if (!array_key_exists($params['order_type'], $businessList)) {
            message('商户未开通该业务！');
        }


        $params['module'] = $this->module['name'];
        $pars = array();
        $pars['uniacid'] = $_W['uniacid'];
        $pars['module'] = $params['module'];
        $pars['tid'] = $params['tid'];

        $log = pdo_get('core_paylog', $pars);

        $plid = $log['plid'];
        if (empty($log)) {
            $log = array(
                'uniacid' => $_W['uniacid'],
                'acid' => $_W['acid'],
                'openid' => $_W['member']['uid'],
                'module' => $this->module['name'],
                'tid' => $params['tid'],
                'fee' => $params['fee'],
                'card_fee' => $params['fee'],
                'status' => '0',
                'is_usecard' => '0',
                'pay_params' => json_encode($params),
            );
            $plid = pdo_insert('core_paylog', $log);
        }elseif ( $log['status'] == 0 && !empty($log['pay_url']) ) {
            //如果有订单且订单未支付，则跳转收银台页面
            header("location:".$log['pay_url']);exit;
        }
        if($log['status'] == '1') {
            message('这个订单已经支付成功, 不需要重复支付.');
        }

        $member = Visitor::getInstance()->getMember() or check_memberauth();

        $order_ls[] = [
            'mercode'=>$mercode,
            'mername'=>$mername,
            'orgcode'=>$params['orgcode'] ? $params['orgcode'] : '',
            'orgname'=>'',
            'netcode'=>$params['netcode'] ? $params['netcode'] : '',
            'netname'=>'',
            'order_amt'=>$params['fee'],
            'is_using_coupon'=>$params['coupon_number']?'Y':'N',
            'coupon_from'=>$params['coupon_number']?'LOCAL':'',
            'coupon_no'=> isset($params['coupon_number'])?[$params['coupon_number']]:[],
            'coupon_dis_amt'=>$params['coupon_total'] ? $params['coupon_total'] : 0,
            'pay_amt'=>$params['fee'],
            'remark'=>$params['pay_title'],
            //业务手续费
            'business_fee_rate' => '0%',
            'business_fee' => 0,
            'business_standard_rate' => '0%',
            'business_standard_fee' => 0,
            'business_cost_rate' => '0%',
            'business_cost_fee' => 0,
        ];

        $body = [
            'order_source'=> 'IN',
            'business_code' => $params['order_type'],
            'order_type'=> $params['order_type'],
            'qrcode_token'=> '',
            'scan_mode'=> "0",
            'operator'=> "0",
            'termcode'=> "0",
            'ext_order_no'=> $params['ordersn'],
            'total_amt'=> $params['fee'],
            'total_fee'=> 0,
            'no_discount_amt'=> 0,
            'total_discount_amt'=> 0,
            'total_pay_amt'=> $params['fee'],
            'dtu_openid'=> $member?$member->getDtuOpenid():0,
            'scene_code'=> $params['scene_code'],//$scene_code,
            'remark'=> $params['pay_title'],
        ];
        $body['order_count'] = count($order_ls);
        $body['order_list'] = $order_ls;
        $body['notify_url'] = C('OPERATIONAL_DOMAIN')."/inner.php";
        //业务总手续费
        $body['total_business_fee'] = 0;
        $body['total_business_standard_fee'] = 0;
        $body['total_business_cost_fee'] = 0;
        $body['channel'] = C('TSP_CHANNEL_DDHY',null,'DDHY');
        $tsp = new DTTSPApi();
        $tsp_pay = $tsp->tsp_create_order($body) or  message('统一下单失败！'); //$tsp->getLastError()
        pdo_update('core_paylog',['pay_url'=>$tsp_pay['pay_url'],'out_trade_no'=>$tsp_pay['main_order_no']],['plid'=>$plid]);
        $callback_url = $_W['siteroot'] . "app/index.php?i={$_W['uniacid']}&c=entry&do=paycallback&m={$params['module']}&out_trade_no={$tsp_pay['main_order_no']}";
        $callback_url = urlencode($callback_url);
        $pay_url = $tsp_pay['pay_url'] . '&order_type='.$params['order_type'].'&callback='.$callback_url;
        header("location:".$pay_url);exit;

//        success(['pay_url'=>$tsp_pay['pay_url'],'payordersn'=>$tsp_pay['main_order_no'],'payType'=>$params['order_type']]);
    }


    public function payResult($ret) {
        global $_W;
        if($ret['from'] == 'return') {
            if ($ret['type'] == 'credit2') {
                message('已经成功支付', url('mobile/channel', array('name' => 'index', 'weid' => $_W['weid'])));
            } else {
                message('已经成功支付', '../../' . url('mobile/channel', array('name' => 'index', 'weid' => $_W['weid'])));
            }
        }
    }


    protected function payResultQuery($tid) {
        $sql = 'SELECT * FROM ' . tablename('core_paylog') . ' WHERE `module`=:module AND `tid`=:tid';
        $params = array();
        $params[':module'] = $this->module['name'];
        $params[':tid'] = $tid;
        $log = pdo_fetch($sql, $params);
        $ret = array();
        if(!empty($log)) {
            $ret['uniacid'] = $log['uniacid'];
            $ret['result'] = $log['status'] == '1' ? 'success' : 'failed';
            $ret['type'] = $log['type'];
            $ret['from'] = 'query';
            $ret['tid'] = $log['tid'];
            $ret['user'] = $log['openid'];
            $ret['fee'] = $log['fee'];
        }
        return $ret;
    }


    protected function grant($params = array()) {
        global $_W, $_GPC;
        if (empty($_W['member']['uid'])) {
            checkauth();
        }
        load()->model('activity');
        $params['module'] = $this->module['name'];
        if(empty($params['module'])) {
            message('模块信息错误', referer(), 'error');
        }
        $iscard = pdo_fetchcolumn('SELECT iscard FROM ' . tablename('modules') . ' WHERE name = :name', array(':name' => $params['module']));
        if(!$iscard) {
            message('模块不支持领取优惠券', referer(), 'error');
        }
        $pindex = max(1, intval($_GPC['page']));
        $psize = 5;
        $user = mc_fetch($_W['member']['uid'], array('groupid'));
        $groupid = $user['groupid'];

        $modules_limit = pdo_fetchall("SELECT couponid FROM ".tablename('activity_coupon_modules')." WHERE uniacid = :uniacid AND module = :module", array(':uniacid' => $_W['uniacid'], ':module' => $params['module']), 'couponid');
        $groups_limit = pdo_fetchall("SELECT couponid FROM ".tablename('activity_coupon_allocation')." WHERE uniacid = :uniacid AND groupid = :groupid", array(':uniacid' => $_W['uniacid'], ':groupid' => $groupid), 'couponid');
        $modules_limit = array_keys($modules_limit);
        $groups_limit = array_keys($groups_limit);
        $intersect = array_intersect($modules_limit, $groups_limit);
        if(empty($intersect)) {
            message('没有该模块适用的优惠券', referer(), 'error');
        }
        $intersect = implode(',', array_values($intersect));
        $par = array(':uniacid' => $_W['uniacid'], ':time' => TIMESTAMP);
        $total = pdo_fetchcolumn('SELECT COUNT(1) FROM ' . tablename('activity_coupon') . " WHERE uniacid = :uniacid AND dosage < amount AND endtime >= :time AND couponid IN ({$intersect})", $par);
        $cards = pdo_fetchall('SELECT * FROM ' . tablename('activity_coupon') . " WHERE uniacid = :uniacid AND dosage < amount AND endtime >= :time AND couponid IN ({$intersect}) ORDER BY endtime ASC LIMIT " . ($pindex - 1) * $psize . ', ' . $psize, $par);
        if(!empty($cards)) {
            foreach($cards as $key => &$card) {
                $has = pdo_fetchcolumn('SELECT COUNT(1) FROM ' . tablename('activity_coupon_record') . ' WHERE uid = :uid AND uniacid = :aid AND couponid = :cid AND status > 0', array(':uid' => $_W['member']['uid'], ':aid' => $_W['uniacid'], ':cid' => $card['couponid']));
                $card['is_grant'] = 1;
                if($card['limit'] <= $has) {
                    $card['is_grant'] = 0;
                }
                $card['grant_num'] = $has;
                $card['grant_url'] = base64_encode(json_encode(array('id' => $card['couponid'], 'm' => $params['module'])));
            }

            $creditnames = array();
            $unisettings = uni_setting($_W['uniacid'], array('creditnames'));
            if (!empty($unisettings) && !empty($unisettings['creditnames'])) {
                foreach ($unisettings['creditnames'] as $key=>$credit) {
                    $creditnames[$key] = $credit['title'];
                }
            }
        }
        $pager = pagination($total, $pindex, $psize, '', array('before' => 0, 'after' => 0));
        include $this->template('common/grant');
    }


    public function grantResult($ret) {
        global $_W;
        if($ret['result'] == 'success') {
            $types = array('', 'coupon', 'token');
            message('领取优惠券成功', url('activity/' . $types[$ret['type']] . '/mine'), 'success');
        }
    }


    public function grantCherk($ret) {
        global $_W;
        return true;
    }


    protected function share($params = array()) {
        global $_W;
        $url = murl('utility/share', array('module' => $params['module'], 'action' => $params['action'], 'sign' => $params['sign'], 'uid' => $params['uid']));
        echo <<<EOF
		<script>
			//转发成功后事件
			window.onshared = function(){
				var url = "{$url}";
				$.post(url);
			}
		</script>
EOF;
    }


    protected function click($params = array()) {
        global $_W;
        $url = murl('utility/click', array('module' => $params['module'], 'action' => $params['action'], 'sign' => $params['sign'], 'tuid' => $params['tuid'], 'fuid' => $params['fuid']));
        echo <<<EOF
		<script>
			var url = "{$url}";
			$.post(url);
		</script>
EOF;
    }

}