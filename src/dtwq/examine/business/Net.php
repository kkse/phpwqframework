<?php
namespace kkse\wqframework\dtwq\examine\business;

use Com\Internal\Bill;
use Com\Internal\DbCache;
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
class Net extends ABusiness
{
    protected $table_name = 'dt_bank_net';
    protected $business_name = '网点管理';

    public function getExamineData($action, array $data)
    {
        switch ($action) {
            case 'add':
                $newdata = [
                    'pkid'=>$data['netcode'],
                    'add'=>$data,
                ];
                break;
            case 'edit':
                $newdata = [
                    'pkid'=>$data['netcode'],
                    'old'=>$this->getOldData($data['netcode']),
                ];

                $newdata['edit'] = array_diff_assoc($data, $newdata['old']);
                break;
            case 'delete':
                $newdata = [
                    'pkid'=>$data['netcode'],
                    'old'=>$data,
                ];
                break;
            default:
                return null;
        }

        return Data::create($newdata);
    }

    public function getOldData($netcode) {
        $item = pdo_get($this->table_name, ['netcode' => $netcode]);
        if (!$item) {
            return null;
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
        $netcode = $data->getPkid();
        switch ($fw->getAction()) {
            case 'add':
                $add_data = $data->getAddData();
                $insert = array_extract($add_data, [
                    'province',
                    'city',
                    'bankname',
                    'abbname',
                    'nettype',
                    'bankno',
                    'video',
                    'thumb',
                    'url',
                    'ship_to',
                    'hotline',
                    'contact_weixinno',
                    'contact_phone',
                    'contact_email',
                    'description',
                    'map_lat',
                    'map_lng',
                    'zoom',
                    'business_hours',
                    'net_spaceid',
                    'contact',
                    'address',
                    'zip',
                    'phone',
                    'mobile',
                    'fax',
                    'email',

                    'parentcode',
                    'orgcode',
                ]);

                $insert['netcode'] = $netcode;
                $insert['status'] = 1;
                $insert['create_time'] = $insert['update_time'] = date('Y-m-d H:i:s');
                $insert['update_user'] = $fw->getAuthor()->getName();

                if (!pdo_insert($this->table_name, $insert)) {
                    throw new \Exception('插入业务数据失败');
                }

                break;
            case 'edit':
                $edit_data = $data->getEditData();
                $update = array_find($edit_data, [
                    'province',
                    'city',
                    'bankname',
                    'abbname',
                    'nettype',
                    'bankno',
                    'video',
                    'thumb',
                    'url',
                    'ship_to',
                    'hotline',
                    'contact_weixinno',
                    'contact_phone',
                    'contact_email',
                    'description',
                    'map_lat',
                    'map_lng',
                    'zoom',
                    'business_hours',
                    'net_spaceid',
                    'contact',
                    'address',
                    'zip',
                    'phone',
                    'mobile',
                    'fax',
                    'email',
                ]);

                $update['update_time'] = date('Y-m-d H:i:s');
                $update['update_user'] = $fw->getAuthor()->getName();

                DbCache::data_update($this->table_name, $update, $netcode);
                break;
            case 'delete':
                DbCache::data_delete($this->table_name, $netcode);
                break;
        }
    }

    public function getExamineInfo($action, Data $data)
    {
        $netcode = $data->getPkid();
        $odata = $data->getOriginalData();
        return $netcode.':'.$odata['bankname'];
    }
}