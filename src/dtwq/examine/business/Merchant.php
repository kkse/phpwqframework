<?php
namespace kkse\wqframework\dtwq\examine\business;

use Com\Internal\Bill;
use kkse\wqframework\dtwq\examine\ABusiness;
use kkse\wqframework\dtwq\examine\Auditor;
use kkse\wqframework\dtwq\examine\Data;
use kkse\wqframework\dtwq\examine\FlowingWater;
use Org\Base\Merchant as MerchantData;

/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/9/15
 * Time: 1:20
 */
class Merchant extends ABusiness
{
    protected $table_name = 'dt_businessman';
    protected $business_name = '商户管理';

    public function getExamineData($action, array $data)
    {
        switch ($action) {
            case 'add':
                $newdata = [
                    'pkid'=>$this->genPkid($data['orgcode']),
                    'add'=>$data,
                ];
                break;
            case 'edit':
                $newdata = [
                    'pkid'=>$data['number'],
                    'old'=>$this->getOldData($data['number']),
                ];

                $newdata['edit'] = array_diff_assoc($data, $newdata['old']);
                break;
            case 'delete':
                $newdata = [
                    'pkid'=>$data['number'],
                    'old'=>$data,
                ];
                break;
            default:
                return null;
        }

        return Data::create($newdata);
    }

    public function genPkid($orgcode)
    {
        return Bill::genSN('MERCODE', $orgcode);
    }

    public function getOldData($mercode) {
        $item = pdo_get($this->table_name, ['number' => $mercode]);
        if (!$item) {
            return null;
        }
        $item['shipping_id'] = 0;
        $item['starting_price'] = 0;
        $item['free_rate'] = 0;
        $item['freight'] = 0;

        //运费费用配置
        if ($item["express"] == '3') {
            $freight_data = pdo_get('dt_businessman_freight', ['bus_id' => $item['id']]);
            if ($freight_data) {
                $item['starting_price'] = intval($freight_data['starting_price']);
                $item['free_rate'] = intval($freight_data['free_rate']);
                $item['freight'] = intval($freight_data['freight']);
                $item['shipping_id'] = intval($freight_data['shipping_id']);
            }
        }

        return $item;
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
        $mercode = $data->getPkid();
        switch ($fw->getAction()) {
            case 'add':
                $add_data = $data->getAddData();
                $insert = array_extract($add_data, [
                    'synopsis',
                    'announce',
                    'business_license',
                    'administration',
                    'business_hours',
                    'contact_address',
                    'contact_way',
                    'freight',
                    'coordinate',
                    'show',
                    'logo',
                    'picture',
                    'is_store',
                    'is_shop',
                    'sort',
                    'is_delivery',
                    'express',
                    'description',
                    'template_code',
                    'circle_id',
                    'short_name',
                    'company_website',
                    'business_scope',
                    'sale_person',
                    'sale_mobile',
                    'protocol',
                    'bank_file',
                    'remark',
                    'is_creditcard',
                    'settle_type',
                    'settle_time',
                    'mertype',
                    'extension_officer',
                    'mer_license',
                    'business_deadline',
                    'business_deadline_end',
                    'orgcode',
                    'netcode',
                    'mobile',
                    'service_mobile',
                    'name',
                    'industrycode',
                    'cateid',
                    'contact_name',
                    'address_area',
                ]);

                $insert['number'] = $mercode;
                $insert['status'] = 1;
                $insert['add_time'] = $insert['update_time'] = date('Y-m-d H:i:s');
                $insert['audit_admin'] = $auditor->getName();
                $insert['audit_time'] = date('Y-m-d H:i:s');

                if (!($bus_id = pdo_insert($this->table_name, $insert))) {
                    throw new \Exception('插入业务数据失败');
                }

                if ($add_data['express'] == 3) {
                    $insert_freight = array_extract($add_data, ['shipping_id', 'starting_price', 'free_rate', 'freight']);
                    $insert_freight['bus_id'] = $bus_id;
                    pdo_insert('dt_businessman_freight', $insert_freight);
                }
                $insert['id'] = $bus_id;
                $mer = new MerchantData($insert);
                $mer->initAccount();
                break;
            case 'edit':
                $edit_data = $data->getEditData();
                $update = array_find($edit_data, [
                    'synopsis',
                    'announce',
                    'business_license',
                    'administration',
                    'business_hours',
                    'contact_address',
                    'contact_way',
                    'freight',
                    'coordinate',
                    'show',
                    'logo',
                    'picture',
                    'is_store',
                    'is_shop',
                    'sort',
                    'is_delivery',
                    'express',
                    'description',
                    'template_code',
                    'circle_id',
                    'short_name',
                    'company_website',
                    'business_scope',
                    'sale_person',
                    'sale_mobile',
                    'protocol',
                    'bank_file',
                    'remark',
                    'is_creditcard',
                    'settle_type',
                    'settle_time',
                    'mertype',
                    'extension_officer',
                    'mer_license',
                    'business_deadline',
                    'business_deadline_end',
                    'orgcode',
                    'netcode',
                    'mobile',
                    'service_mobile',
                    'name',
                    'industrycode',
                    'cateid',
                    'contact_name',
                    'address_area',
                ]);

                $update['update_time'] = date('Y-m-d H:i:s');
                $update['audit_admin'] = $auditor->getName();
                $update['audit_time'] = date('Y-m-d H:i:s');

                $mer = new MerchantData($data->getOldData());
                $mer->update($update);

                if ($mer['express'] == 3) {
                    $update_freight = array_extract($edit_data, ['shipping_id', 'starting_price', 'free_rate', 'freight']);
                    $update_freight['bus_id'] = $mer->getId();
                    pdo_insert('dt_businessman_freight', $update_freight, true);
                }
                break;
            case 'delete':
                $mer = new MerchantData($data->getOldData());
                //$item_bank = pdo_get('dt_businessman_bank', ['bus_id'=>$old_data['id']]);

                //判断是否已经绑卡
                if ($mer->isBindCard()) {
                    $mer->unBindCard();//解除绑卡
                }

                $mer->delete();
                break;
        }
    }

    public function getExamineInfo($action, Data $data)
    {
        $mercode = $data->getPkid();
        $odata = $data->getOriginalData();
        return $mercode.':'.$odata['name'];
    }
}