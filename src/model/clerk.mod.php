<?php
/**
 * [ECOCOM System] Copyright (c) 2014 DTROAD.COM
 * ECOCOM is NOT a free software, it under the license terms, visited http://www.dtroad.com/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

function clerk_check() {
	global $_W;
	if(empty($_W['openid'])) {
		return error(-1, '获取粉丝openid失败');
	}
	$data = pdo_get('activity_clerks', array('uniacid' => $_W['uniacid'], 'openid' => $_W['openid']));
	if(empty($data)) {
		return error(-1, '不是操作店员');
	}
	return $data;
}

function clerk_permission_list() {
	$data = array(
		'mc' => array(
			'title' => '快捷交易',
			'permission' => 'mc_manage',
			'items' => array(
				array(
					'title' => '积分充值',
					'permission' => 'mc_credit1',
					'icon' => 'fa fa-money',
					'type' => 'modal',
					'modal' => 'modal-trade',
					'data' => 'credit1',
				),
				array(
					'title' => '余额充值',
					'permission' => 'mc_credit2',
					'icon' => 'fa fa-cny',
					'type' => 'modal',
					'modal' => 'modal-trade',
					'data' => 'credit2',
				),
				array(
					'title' => '消费',
					'permission' => 'mc_consume',
					'icon' => 'fa fa-usd',
					'type' => 'modal',
					'modal' => 'modal-trade',
					'data' => 'consume',
				),
				array(
					'title' => '发放会员卡',
					'permission' => 'mc_card',
					'icon' => 'fa fa-credit-card',
					'type' => 'modal',
					'modal' => 'modal-trade',
					'data' => 'card',
				),
			)
		),
		'activity' => array(
			'title' => '系统优惠券核销',
			'permission' => 'activity_card_manage',
			'items' => array(
				array(
					'title' => '折扣券核销',
					'permission' => 'activity_consume_coupon',
					'icon' => 'fa fa-money',
					'type' => 'url',
					'url' => './index.php?c=activity&a=consume&do=display&type=1'
				),
				array(
					'title' => '代金券核销',
					'permission' => 'activity_consume_token',
					'icon' => 'fa fa-money',
					'type' => 'url',
					'url' => './index.php?c=activity&a=consume&do=display&type=2'
				),
			)
		),

		'wechat' => array(
			'title' => '微信卡券核销',
			'permission' => 'wechat_card_manage',
			'items' => array(
				array(
					'title' => '卡券核销',
					'permission' => 'wechat_consume',
					'icon' => 'fa fa-money',
					'type' => 'url',
					'url' => './index.php?c=wechat&a=consume'
				)
			)
		),
	);
	return $data;
}




