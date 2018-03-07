<?php
namespace kkse\wqframework\dtwq\examine\business;

use kkse\wqframework\dtwq\examine\ABusiness;
use kkse\wqframework\dtwq\examine\Auditor;
use kkse\wqframework\dtwq\examine\Data;
use kkse\wqframework\dtwq\examine\FlowingWater;

/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/9/15
 * Time: 1:20
 */
class QrcodeApply extends ABusiness
{
    protected $table_name = 'dt_qrcode_pregenerated_apply';
    protected $business_name = '二维码导出管理';
    protected $actions = ['add'];//只做新增

    public function getExamineData($action, array $data)
    {
        switch ($action) {
            case 'add':
                $newdata = [
                    'pkid'=>$data['batch_no'],
                    'add'=>$data,
                ];
                break;
            default:
                return null;
        }

        return Data::create($newdata);
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
        $batch_no = $data->getPkid();
        switch ($fw->getAction()) {
            case 'add':
                $add_data = $data->getAddData();
                $insert = array_extract($add_data, [
                    'quantity',
                    'token_length',
                    'orgcode',
                    'remark',
                ]);

                $insert['batch_no'] = $batch_no;
                $insert['status'] = 1;
                $insert['create_time'] = date('Y-m-d H:i:s');
                $insert['operator'] = $fw->getAuthor()->getName();

                if (!pdo_insert($this->table_name, $insert)) {
                    throw new \Exception('插入业务数据失败');
                }

                break;
        }
    }

    public function getExamineInfo($action, Data $data)
    {
        return $data->getPkid();
    }
}