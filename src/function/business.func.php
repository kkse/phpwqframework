<?php
use Org\Account\BusAccount;
use Com\Internal\Push;
use Org\Base\Factory as BaseFactory;


/**
 * 商户绑卡公共方法 xlan20170801
 * @param int $id 绑卡表id
 */
function merBindCards($id)
{
    $log = logger('bank');
    $log->addInfo('进入商户绑卡公共方法');
    $bank = pdo_get('dt_businessman_bank', ['id'=>$id]);

    $merchant = BaseFactory::getMerchant($bank['bus_id'], 'id');

    if ($merchant['bank_status'] == '1') {
        $log->addInfo('商户已绑卡，绑卡流程结束');
        $fail_data = array("status" => 3, 'fail_reason' => '已经绑卡');
        //已经绑卡就不能再绑卡了，需要先解绑再绑新卡，以后再考虑自动解绑
        pdo_update('dt_businessman_bank', $fail_data, array("id" => $id, "bus_id" => $bank['bus_id']));
    } else {
        $merAccount = new BusAccount($merchant);
        $self_check_card = C('MERCHANT_SELF_CHECK_CARD', null, 'N');//参数默认值改为N  xlan20170612
        $log->addInfo('进行绑卡动作', ['MERCHANT_SELF_CHECK_CARD' => $self_check_card] + $bank);
        //$result = '';
        // 验卡
        if ($self_check_card == 'Y') {
            //验卡：河源
            $result = $merAccount->bindBankCardsSCC($bank['bank_card'], $bank['mobile'], $bank['nickname'], $bank['id_card'], $bank['id'], $bank['bank_name'], $bank['acc_nature']);
        } else {
            //不验卡：顺德、鲜特汇
            $result = $merAccount->bindBankCards($bank['bank_card'], $bank['mobile'], $bank['nickname'], $bank['id_card'], $bank['id'], $bank['acc_nature'], $bank['bank_acc_type']);
        }
        $log->addInfo('绑卡接口返回结果:', is_array($result) ? $result : [$result]);

        if ($result) {
            $bus_data = array(
                "bank_card" => $bank['bank_card'],
                "bank_username" => $bank['nickname'],
                "bank_mobile" => $bank['mobile'],
                'bank_deposit' => $result['bankname'],
                'bank_status' => '1',
            );
            pdo_update('dt_businessman', ['id' => $merchant->getId()], $bus_data)
            and BaseFactory::cleanCache($merchant);

            $success_data = array(
                'status' => '4'
            );
            pdo_update('dt_businessman_bank', $success_data, array('id' => $id));
            $log->addInfo('绑卡成功，流程结束');
            //绑卡成功发送短信通知
            Push::sendSms($merchant->getObligateMobile(), 'mer.bindcard.success', ['NAME' => $merchant->getMerName()]);
        } else {
            $log->addInfo('卡信息验证失败，绑卡失败，流程结束');
            $fail_data = array("status" => 3);     //更新状态验证失败
            pdo_update('dt_businessman_bank', $fail_data, array('id' => $id));
        }
    }
}

function TL($name){
    global $table_config;
    if (isset($table_config[$name]['field_name'])) {
        echo $table_config[$name]['field_name'];
    } else {
        echo $name;
    }
}


function showPermissionCode($item = null)
{
    $html = '';

    if (is_array($item)) {
        $orgname = $item["orgcode"]
            ?\dtwq\base\Org::getOrgName($item["orgcode"])
            :'无';
        $html .= "<td>{$orgname}</td>";

        $netname = $item["netcode"]
            ?\Com\Internal\DbCache::data_getcolumn('dt_bank_net', $item["netcode"], 'bankname')
            :'';
        $html .= "<td>".($netname?:'无')."</td>";
    } else {
        $html .= '<th style="width:100px">所属机构</th>';
        $html .= '<th style="width:100px">所属网点</th>';
    }

    return $html;
}


//后台管理员
function user()
{
    return pdo_getpairs('dti_users', [], 'uid', 'username');
}


//机构
function orglist()
{
    $orglist = CT('org')->where(['status' => 1])->getPairs('orgcode', 'name');
    return $orglist;

}

//店铺 number
function store_list($list, $mercode = 'mercode')
{
    $store_list = array();
    if ($list) {
        $mercodes = [];
        foreach ($list as $val) {
            if ($val[$mercode]) {
                $mercodes[$val[$mercode]] = $val[$mercode];
            }

        }
        if (count($mercodes) > 0) {
            $store_list = pdo_getpairs('dt_businessman', ['number'=>['in', $mercodes]], 'number', 'name');
        }
    }

    return $store_list;

}

//交易分类
function trans_class()
{
    return pdo_getpairs('dt_pub_transcode', [], 'transcode', 'name');
}

//组
function org_dep()
{
    return pdo_getpairs('dt_org_dep', [], 'depcode', 'depname');
}

//支付方式
function pay_way()
{
    return pdo_getpairs('dt_pay_paycode', [], 'pay_code', 'payname');
}

//收银员
function cashier()
{
    return pdo_getpairs('dt_businessman_auth', [], 'id', 'name');
}

//会员卡类型
function card_type()
{
    return pdo_getpairs('dt_card_cardtype', [], 'card_type', 'type_name');
}

//业务类型
function busniess_type()
{
    return pdo_getpairs('dt_pub_businesstype', [], 'businesscode', 'name');
}

//拓展人
function sale_person($orgcode = '')
{
    $where = [];
    $orgcode and $where['orgcode'] = $orgcode;
    return pdo_getall('dt_org_person', $where, 'person_no,personname,orgcode,depcode');
}

//收费种类
function fee_code($orgcode)
{
    $where = ['status'=>1];
    $orgcode and $where['orgcode'] = $orgcode;
    return pdo_getall('dt_pub_feeitem', $where, 'feeitemcode,feename,period,s_amt,amt,high_amt');
}