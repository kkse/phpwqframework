<?php
namespace kkse\wqframework\dtwq\examine\business;

use Com\Api\AlipayApi;
use Com\Internal\Push;
use kkse\wqframework\dtwq\base\Org;
use kkse\wqframework\dtwq\examine\ABusiness;
use kkse\wqframework\dtwq\examine\Auditor;
use kkse\wqframework\dtwq\examine\Data;
use kkse\wqframework\dtwq\examine\FlowingWater;
use kkse\wqframework\dtwq\examine\FWItem;
use Org\Base\Factory as BaseFactory;
use Org\MainPayment\PayType\Bankzfb;
use Org\Qrcode;
use Overtrue\Wechat\Merchant\MerchantCmd;
use Overtrue\Wechat\Merchant\SubMerchant;
use Org\Base\Merchant;

/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/9/15
 * Time: 1:20
 */
class MerBusiness extends ABusiness
{
    protected $table_name = 'dt_mer_business';
    protected $business_name = '商户业务管理';

    protected $business_rule = [
        ['mercode', '商户编号', '', ''],
        ['mername', '商户名称', '', ''],
        ['businesscode', '业务代码', '', 'translateBusinessName'],
        ['openflag', '是否已开通', '', ['否','是']],
        ['opendate', '开通日期', '', ''],
        ['protocalno', '协议号', '', ''],
        //['sale_idtype', '拓展单位ID类别', '', ''],
        //['sale_unitcode', '拓展单位编号', '', ''],
        //['sale_person', '拓展人', '', ''],
        ['feecode', '费用种类', '', ''],
        ['fee_period', '收费频度', '', ''],
        ['fee', '费用', '', ''],
        //['status', '状态', '', ''],
        ['bind_qrcode_status', '是否激活二维码', '', ['不绑定','绑定并立即激活', '绑定待激活'], ['businesscode'=>'CASHIERPAY']],//:1绑定并立即激活  2绑定待激活  0不绑定
        ['voucher_no', '二维码凭证号', '', '', ['businesscode'=>'CASHIERPAY']],
        ['outer_mertype', '外部商户号类型', '', ['WX'=>'微信', 'ALIPAY'=>'支付宝'], ['businesscode'=>['WECHATSD','ZFBSD']]],
        ['out_mercode', '外部商户号', '', '', ['businesscode'=>['WECHATSD','ZFBSD']]],
        ['outer_merstatus', '外部商户号申请状态', '', ['P'=>'申请中', 'S'=>'成功','F'=>'失败',], ['businesscode'=>['WECHATSD','ZFBSD']]],//(P-申请中；S-成功,F-失败)
        ['failure_reason', '失败原因', '', '', ['businesscode'=>['WECHATSD','ZFBSD']]],
        ['mcc', 'MCC编号', '', 'translateMCC', ['businesscode'=>['WECHATSD','ZFBSD']]],
        ['rate_channel', '费率渠道', '', ['zero'=>'0%费率', '0.002'=>'2%费率'], ['businesscode'=>'WECHATSD']],
        ['id_card_no', '身份证号', '', '', ['businesscode'=>'ZFBSD']],
        ['address_area', '地址区域编码', '', 'dtwq\\base\\Area\\Area::getName', ['businesscode'=>'ZFBSD']],
        ['is_flowers', '是否支持花呗', '', ['Y'=>'是','N'=>'否'], ['businesscode'=>'ZFBSD']],
        ['contact_address', '注册地址', '', '', ['businesscode'=>'ZFBSD']],
        //['is_push', '标识已推送过风险交易商户详细资料的商户', '', ['未推送','已推送']],//(is_push:0未推送 1已推送)
        //['dt_accno', '账户号', '', ''],
        ['memo', '备注', '', ''],
    ];

    public function getExamineData($action, array $data)
    {
        switch ($action) {
            case 'add':
                $newdata = [
                    'pkid'=>$data['mercode'].'_'.$data['businesscode'],
                    'add'=>$data,
                ];
                break;
            case 'edit':
                $newdata = [
                    'pkid'=>$data['mercode'].'_'.$data['businesscode'],
                    'old'=>$this->getOldData($data['mercode'], $data['businesscode']),
                ];

                $newdata['edit'] = array_diff_assoc($data, $newdata['old']);
                break;
            case 'delete':
                $newdata = [
                    'pkid'=>$data['mercode'].'_'.$data['businesscode'],
                    'old'=>$data,
                ];
                break;
            default:
                return null;
        }

        return Data::create($newdata);
    }


    public function getOldData($mercode, $businesscode) {
        $item = pdo_get($this->table_name, ['mercode' => $mercode, 'businesscode' => $businesscode]);
        if (!$item) {
            return null;
        }
        return $item;
    }

    protected function doWxOpened(array $data, Merchant $mer, array $old = [])
    {
        logger('cashierpay')->addInfo('['.$mer->getMerCode().'] 微信收单业务 ======= 这个不用看');

        $data = $data+$old;

        $update_data = [];
        $update_data['outer_mertype'] = 'WX';

        $pay_code = C('MER_BUSINESS_WXPAYCODE');
        if ($pay_code) {
            $pay = M('PayPaycode')
                ->where(['paycode'=>$pay_code])
                ->find();
        } else {
            $pay = M('PayPaycode')
                ->where(['payclass'=>'WXPAYSD'])
                ->find();
        }


        if ($pay) {
            $config = json_decode(htmlspecialchars_decode($pay['config']), true);
            $mch_info = [
                'appid'=>$pay['public_acc'],
                'mch_id'=>$config['mch_id'],
                'mch_key'=>$config['mch_key'],
            ];

            $channel_id = '';
            empty($config['channel_id']) or $channel_id = $config['channel_id'];
            $org = Org::getOrg($mer->getOrgcode());
            if ($org && $org['wx_channel_id']) {
                $channel_id = $org['wx_channel_id'];
            }

            $submer = new SubMerchant([
                'merchant_name'=>$mer->getMerName(),
                'merchant_shortname'=>$mer->getShortName(),
                'service_phone'=>$mer->getServiceMobile(),
                'contact'=>$mer->getContactName(),
                'business'=>$data['mcc'],
                'merchant_remark'=>$data['memo'],
                'channel_id'=>$channel_id?:$mch_info['mch_id'],//渠道号
            ]);

            $cmd = new MerchantCmd($mch_info['appid'], $mch_info['mch_id'], $mch_info['mch_key']);
            $cmd->setClientKey(CERT_PATH.$mch_info['mch_id'].'.wechat.client_key');
            $cmd->setClientCert(CERT_PATH.$mch_info['mch_id'].'.wechat.client_cert');

            $update_data['outer_merstatus'] = 'P';
            //$update_data['request_body'] = json_encode($mch_info + $submer->toArray(), JSON_UNESCAPED_UNICODE);
            //$update_data['request_time'] = date('Y-m-d H:i:s');
            try {
                $ret = $cmd->add($submer);
                $update_data['outer_merstatus'] = 'S';
                $update_data['out_mercode'] = $ret['sub_mch_id'];
            } catch (\Exception $e) {
                //$update_data['response_body'] = '';
                //$update_data['response_time'] = date('Y-m-d H:i:s');
                $update_data['failure_reason'] = $e->getMessage();
                $update_data['outer_merstatus'] = 'F';
                //$update_business['status'] = -1;
            }
        } else {
            $update_data['failure_reason'] = '没有配置微信收单的支付方式';
            $update_data['outer_merstatus'] = 'F';
        }

        pdo_update($this->table_name, $update_data, ['mercode'=>$data['mercode'], 'businesscode'=>$data['businesscode']]);
    }

    protected function doZfbOpened(array $data, Merchant $mer, array $old = [])
    {
        logger('cashierpay')->addInfo('['.$mer->getMerCode().'] 支付宝收单业务  ======= 这个不用看');

        $data = $data+$old;

        //是否申请花呗
        $bus_data =[
            'is_flowers'=>$data['is_flowers'],
            'address_area'=>$data['address_area'],
            'contact_address'=>$data['contact_address'],
        ];

        $mer->update($bus_data);

        $update_data = [];
        $update_data['outer_mertype'] = 'ZFB';

        $pay_code = C('MER_BUSINESS_ZFBPAYCODE');
        if ($pay_code) {
            $pay = M('PayPaycode')
                ->where(['paycode'=>$pay_code])
                ->find();
        } else {
            $pay = M('PayPaycode')
                ->where(['payclass'=>'BANKZFB'])
                ->find();
        }

        if ($pay) {
            $app_id = $pay['public_acc'];
            $source = $pay['password'];
            $config = json_decode(htmlspecialchars_decode($pay['config']), true);
            $config or $config = [];
            $alipay_public_key = file_get_contents(CERT_PATH.$app_id.'.alipay.public_key');
            $merchant_private_key = file_get_contents(CERT_PATH.$app_id.'.alipay.private_key');

            $mch_info = [
                'app_id' => $app_id,
                'mode'=>isset($config['mode'])?$config['mode']:'',
                'alipay_public_key' => $alipay_public_key,
                //商户私钥，您的原始格式RSA私钥
                'merchant_private_key' => $merchant_private_key,
            ];

            $merinfo = [
                'external_id' => $mer->getMerCode(),
                'name' => $mer->getMerName(),
                'alias_name' => $mer->getShortName(),
                'service_phone' => $mer->getServiceMobile(),
                'category_id' => $data['mcc'],
                'source' => $source,
                //'business_license'=>'',
                //'business_license_type'=>'',
                'contact_info' => [
                    [
                        'name'=>$mer->getContactName(),
                        //'phone'=>'',
                        'mobile'=>$mer->getObligateMobile(),
                        //'email'=>'',
                        'type'=>$mer->getMerType()=='CORPORATE'?'LEGAL_PERSON':'CONTROLLER',
                        'id_card_no'=>$data['id_card_no'],
                    ]
                ],
                'memo'=>$data['memo'],
            ];

            $aa = $mer->getAddressArea();
            $ca = $mer->getContactAddress();

            if ($aa && $ca) {
                /*
                    province_code         N    Y        商户所在省份编码
                    city_code             N    Y        商户所在城市编码
                    district_code         N    Y        商户所在区县编码
                    address               N    Y        商户详细经营地址
                    longitude             N    N        经度，浮点型，小数点后最多保留6位
                    latitude              N    N        纬度，浮点型，小数点后最多保留6位
                    type                  N    N        地址联系。取值范围：BUSINESS_ADDRESS:经营地址（默认）；REGISTERED_ADDRESS:注册地址
                 */
                $merinfo['address_info'][] = [
                    'province_code'   => substr($aa, 0, 2).'0000',
                    'city_code'       => substr($aa, 0, 4).'00',
                    'district_code'   => $aa,
                    'address'         => $ca,
                    'type'            => 'BUSINESS_ADDRESS'
                ];
            }

            $update_data['outer_merstatus'] = 'P';
            //$update_data['request_body'] = json_encode(['app_id'=>$app_id] + $merinfo, JSON_UNESCAPED_UNICODE);
            //$update_data['request_time'] = date('Y-m-d H:i:s');

            $api = new AlipayApi();
            if (!empty($data['out_mercode'])) {
                $merinfo['sub_merchant_id'] = $data['out_mercode'];
                $ret = $api->merchantModify($mch_info, $merinfo);
            } else {
                $ret = $api->merchantCreate($mch_info, $merinfo);
            }

            if ($ret) {
                $update_data['failure_reason'] = '';
                $update_data['outer_merstatus'] = 'S';
                $update_data['out_mercode'] = $ret['sub_merchant_id'];
            } else {
                //$update_data['response_body'] = '';
                //$update_data['response_time'] = date('Y-m-d H:i:s');
                $update_data['failure_reason'] = $api->getLastErrno().':'.$api->getLastError();
                $update_data['outer_merstatus'] = 'F';
                //$update_data['status'] = -1;
            }
        } else {
            $update_data['failure_reason'] = '没有配置支付宝收单的支付方式';
            $update_data['outer_merstatus'] = 'F';
            //$update_data['status'] = -1;
        }

        pdo_update($this->table_name, $update_data, ['mercode'=>$data['mercode'], 'businesscode'=>$data['businesscode']]);
    }

    public function doCashierpay(array $data, Merchant $mer, array $old = [])
    {
        //添加日志 start
        logger('cashierpay')->addInfo('['.$mer->getMerCode().'] 收银台业务审核通过，流程开始 ============');
        //添加日志 end

        if ($old) {
            if (isset($data['voucher_no'])) {
                isset($data['bind_qrcode_status']) or $data['bind_qrcode_status'] = $old['bind_qrcode_status'];
                Qrcode::businessDataCheck($old['mercode'], $old['bind_qrcode_status'], $data['bind_qrcode_status'], $old['voucher_no'], $data['voucher_no']);

                //添加日志 start
                $logger_params = array(
                    "商户号"=>$old['mercode'],
                    "原凭证号"=>$old['voucher_no'],
                    "新凭证号"=>$data['voucher_no']
                );
                logger('cashierpay')->addInfo('['.$old['mercode'].'] 流程结束',$logger_params);
                //添加日志 end
            }

        } else {
            Qrcode::businessDataAdd($mer->getMerCode(), $data['voucher_no'], $data['businesscode'], $data['bind_qrcode_status']);

            //添加日志 start
            $logger_params = array(
                "商户号"=>$mer->getMerCode(),
                "凭证号"=>$data['voucher_no'],
            );
            logger('cashierpay')->addInfo('['.$mer->getMerCode().'] 流程结束',$logger_params);
            //添加日志 end

            Push::sendSms($mer->getObligateMobile(), 'mer.notice.business.cashier_success', ['mercode'=>$data['mercode']]);//商户.通知.成功开通收银台业务
        }
    }

    public function doProperty(array $data, Merchant $mer)
    {
        //商户物业业务
        $quarty_data= array(
            'mercode'=>$data['mercode'],
            'title'=>$mer->getMerName(),
            'createtime'=>date("YmdHis"),
            'telphone'=>$mer->getObligateMobile(),
            'topPicture'=>$mer['picture'],
            'weid'=>get_uniacid(),
        );
        pdo_insert('quarter_property', $quarty_data);
    }

    /**
     * 通过
     * @param FlowingWater $fw
     * @param Auditor $auditor
     * @throws \Exception
     */
    public function adopt(FlowingWater $fw, Auditor $auditor)
    {
        $data = $fw->getData();
        list($mercode, $businesscode) = explode('_', $data->getPkid());
        switch ($fw->getAction()) {
            case 'add':
                $add_data = $data->getAddData();
                $insert = array_extract($add_data, [
                    'mername',
                    'openflag',
                    'protocalno',
                    'memo',
                    'sale_person',
                    //'update_time',
                    'feecode',
                    'fee_period',
                    'fee',
                ]);

                $insert['mercode'] = $mercode;
                $insert['businesscode'] = $businesscode;
                $insert['status'] = 1;
                $insert['openflag'] = 1;
                $insert['opendate'] = date('Y-m-d');
                $insert['update_time'] = date('Y-m-d H:i:s');
                $insert['operator'] = $fw->getAuthor()->getName();
                $insert['checker'] = $auditor->getName();
                $insert['checktime'] = date('Y-m-d H:i:s');

                $mer = BaseFactory::getMerchant($mercode);

                switch ($businesscode) {
                    case 'WECHATSD':
                        $insert['mcc'] = $add_data['mcc'];
                        //$insert = $this->doWxOpened($insert, $mer);
                        break;
                    case 'ZFBSD':
                        $insert['is_flowers'] = $add_data['is_flowers'];
                        $insert['address_area'] = $add_data['address_area'];
                        $insert['contact_address'] = $add_data['contact_address'];
                        $insert['id_card_no'] = $add_data['id_card_no'];
                        $insert['mcc'] = $add_data['mcc'];

                        //$insert = $this->doZfbOpened($insert, $mer);
                        break;
                }

                if (!pdo_insert($this->table_name, $insert)) {
                    throw new \Exception('插入业务数据失败');
                }

                switch ($businesscode) {
                    case 'WECHATSD':
                        $this->doWxOpened($insert, $mer);
                        break;
                    case 'ZFBSD':
                        $this->doZfbOpened($insert, $mer);
                        break;
                    case 'CASHIERPAY':
                        $this->doCashierpay($insert, $mer);
                        break;
                    case 'PROPERTY':
                        $this->doProperty($insert, $mer);
                        break;
                }
                break;
            case 'edit':
                $edit_data = $data->getEditData();
                $update = array_find($edit_data, [
                    'protocalno',
                    'memo',
                    'sale_person',
                    'feecode',
                    'fee_period',
                    'fee',
                ]);

                $update['update_time'] = date('Y-m-d H:i:s');
                $update['operator'] = $fw->getAuthor()->getName();
                $update['checker'] = $auditor->getName();
                $update['checktime'] = date('Y-m-d H:i:s');

                $mer = BaseFactory::getMerchant($mercode);

                switch ($businesscode) {
                    case 'WECHATSD':
                        isset($edit_data['mcc']) and $update['mcc'] = $edit_data['mcc'];
                        break;
                    case 'ZFBSD':
                        isset($edit_data['is_flowers']) and $update['is_flowers'] = $edit_data['is_flowers'];
                        isset($edit_data['address_area']) and $update['address_area'] = $edit_data['address_area'];
                        isset($edit_data['contact_address']) and $update['contact_address'] = $edit_data['contact_address'];
                        isset($edit_data['id_card_no']) and $update['id_card_no'] = $edit_data['id_card_no'];
                        isset($edit_data['mcc']) and $update['mcc'] = $edit_data['mcc'];
                        break;
                }

                if (!pdo_update($this->table_name, $update, ['mercode'=>$mercode, 'businesscode'=>$businesscode])) {
                    throw new \Exception('更新业务数据失败');
                }

                switch ($businesscode) {
                    case 'WECHATSD':
                        $this->doWxOpened($update, $mer, $data->getOldData());
                        break;
                    case 'ZFBSD':
                        $this->doZfbOpened($update, $mer, $data->getOldData());
                        break;
                    case 'CASHIERPAY':
                        $this->doCashierpay($update, $mer, $data->getOldData());
                        break;
                }
                //更新缓存
                break;
            case 'delete':
                pdo_delete($this->table_name, ['mercode'=>$mercode, 'businesscode'=>$businesscode]);
                break;
        }

        Merchant::cleanMerBusiness($mercode);
    }

    public function getExamineInfo($action, Data $data)
    {
        list($mercode, $businesscode) = explode('_', $data->getPkid());
        $mer = BaseFactory::getMerchant($mercode);
        $name = $this->translateBusinessName($businesscode);

        return sprintf('商户:%s,业务:%s', $mer->getMerName(), $name);
    }

    public function translateBusinessName($businesscode)
    {
        $name = pdo_getcolumn('dt_pub_businesstype', ['businesscode' => $businesscode], 'name');
        return $name?:'未知';
    }

    public function translateMCC($mcc, $data)
    {
        if($data['businesscode'] == 'ZFBSD'){
            $categorys = Bankzfb::$CATEGORYS;
            $name = $categorys[$mcc];
            return $name;
        } elseif ($data['businesscode'] == 'WECHATSD') {
            $mcc_row = M('dt_pub_mcc', null)->where(['mcc'=>$mcc,'status'=>1])->find();
            if ($mcc_row) {
                $name = $mcc_row['sup_mcc'].'-'.$mcc_row['sec_mcc'].'-'.$mcc_row['name'];
                return $name;
            }
        }

        return '未知';
    }

    public function getEditUrl(FWItem $fwitem)
    {
        if ($fwitem->getBusinessAction() == 'delete') {
            return '';
        }
        $data = $fwitem->getExamineData(true);
        $new_data = $data->getNewData();
        $mercode = $new_data['mercode'];
        $businesscode = $new_data['businesscode'];
        return url('dt/businessman/post_business', ['mercode'=>$mercode,'id'=>$businesscode]);
    }



}