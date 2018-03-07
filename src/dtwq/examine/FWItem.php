<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/11/21
 * Time: 11:29
 */

namespace kkse\wqframework\dtwq\examine;

use kkse\wqframework\dtwq\base\OrgDep;
use lang\RowObject;

/**
 * 审核流水表对象
 * Class FWTable
 * @package dtwq\examine
 */
class FWItem extends RowObject
{
    protected $business_key;
    protected $business_value;
    protected $business_action;
    protected $author_id;
    protected $author_type;
    protected $author_type_value;
    protected $examine_depcodes;

    protected $examined_num;
    protected $current_depcode;
    protected $examine_status;
    protected $refusal_reason;
    protected $refuse_auditor;
    protected $pass_auditors;

    protected $examine_data;
    protected $add_time;
    protected $update_time;


    /**
     * @var Data
     */
    protected $_examine_data;

    /**
     * @var Author
     */
    protected $_author;



    /**
     * @return mixed
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }


    /**
     * @return mixed
     */
    public function getBusinessKey()
    {
        return $this->business_key;
    }

    public function getBusiness()
    {
        return ABusiness::getBusiness($this->getBusinessKey());
    }

    /**
     * @return mixed
     */
    public function getBusinessValue()
    {
        return $this->business_value;
    }

    /**
     * @return mixed
     */
    public function getBusinessAction()
    {
        return $this->business_action;
    }

    /**
     * @return mixed
     */
    public function getAuthorId()
    {
        return $this->author_id;
    }

    /**
     * @return mixed
     */
    public function getAuthorType()
    {
        return $this->author_type;
    }

    /**
     * @return mixed
     */
    public function getAuthorTypeValue()
    {
        return $this->author_type_value;
    }

    /**
     * @return Author|null
     */
    public function getAuthor()
    {
        if (!isset($this->_author)) {
            $this->_author = Author::loadAuthor($this->getAuthorType(), $this->getAuthorId())
            or $this->_author = false;
        }
        return $this->_author?:null;
    }

    /**
     * 已经审核的数量
     * @return mixed
     */
    public function getExaminedNum()
    {
        return $this->examined_num;
    }

    //获取需要审核的次数
    public function getExamineNum()
    {
        if ($this->examine_depcodes) {
            return count(explode('|',$this->examine_depcodes));
        }
        return 0;
    }

    /**
     * @return mixed
     */
    public function getExamineDepcodes()
    {
        return $this->examine_depcodes;
    }

    /**
     * @return mixed
     */
    public function getCurrentDepcode($getlist = false)
    {
        if ($getlist) {
            return explode(',', $this->current_depcode);;
        }
        return $this->current_depcode;
    }

    public function getCurrentDepcodeInfo()
    {
        $info = [];
        foreach ($this->getCurrentDepcode(true) as $current_depcode) {
            $info[] = $current_depcode.':'.OrgDep::getOrgDepName($current_depcode);
        }
        return implode('|', $info);
    }

    public function getNextDepcode()
    {
        $index = $this->getExaminedNum();
        $examine_depcodes = explode('|',$this->examine_depcodes);
        if (isset($examine_depcodes[$index+1])) {
            return $examine_depcodes[$index+1];
        }

        return '';
    }

    /**
     * @return mixed
     */
    public function getExamineStatus()
    {
        return $this->examine_status;
    }

    /**
     * @param bool $compile
     * @return Data|mixed
     */
    public function getExamineData($compile = false)
    {
        if ($compile) {
            if (!isset($this->_examine_data)) {
                $this->_examine_data = Data::load($this->examine_data)
                or $this->_examine_data = false;
            }

            return $this->_examine_data?:null;
        }
        return $this->examine_data;
    }

    /**
     * @return mixed
     */
    public function getRefusalReason()
    {
        return $this->refusal_reason;
    }

    /**
     * @return mixed
     */
    public function getRefuseAuditor()
    {
        return $this->refuse_auditor;
    }

    /**
     * @return mixed
     */
    public function getPassAuditors()
    {
        return $this->pass_auditors;
    }

    /**
     * @return mixed
     */
    public function getAddTime()
    {
        return $this->add_time;
    }

    public function backup($backup = true)
    {
        $ret = pdo_delete(config('DB_PREFIX').'examine_flowing_water', [
            'business_key'=>$this->getBusinessKey(),
            'business_value'=>$this->getBusinessValue(),
        ]);

        if ($ret && $backup) {
            M()->table(config('DB_PREFIX').'examine_flowing_water_history')
                ->insert($this->_data);
        }

        return $ret;
    }

    public function update(array $update, array $add_where = [])
    {
        unset($update['business_key'], $update['business_value']);
        if (!$update) {
            return true;
        }
        if ($ret = pdo_update(config('DB_PREFIX').'examine_flowing_water', $update, [
            'business_key'=>$this->getBusinessKey(),
            'business_value'=>$this->getBusinessValue()
        ] + $add_where)) {
            $this->_updateData($update);
        }

        return $ret;
    }

    public function updateData(Data $data)
    {
        $update = [
            'examine_status'=>'W',//更新数据只能退回审核状态先
            'examine_data'=>$data->__toString(),
        ];

        if ($ret = $this->update($update)) {
            $this->_examine_data = $data;
        }
        return $ret;
    }

    public function submit()
    {
        $update = [
            'examined_num'=>0,
            'current_depcode'=>'',
            'examine_status'=>'P',
            'refusal_reason'=>'',
            'refuse_auditor'=>'',
            'pass_auditors'=>'',
            'update_time' => date('Y-m-d H:i:s'),
        ];

        $examine_depcodes = explode('|', $this->examine_depcodes);
        $update['current_depcode'] = reset($examine_depcodes);

        return $this->update($update);
    }

    public static function createInstance(array $data)
    {
        //禁止对同一个数据进行不同的动作审核
        //pk:business_key\business_value
        //business_action\author_id\author_type:author_type_value
        //

        $ret = M()->table(config('DB_PREFIX').'examine_flowing_water')
            ->insert($data);
        if (!$ret) {
            return null;
        }

        return new self($data);
    }

    public static function findInstance($business_key, $business_value)
    {
        $data = pdo_get(config('DB_PREFIX').'examine_flowing_water', [
            'business_key'=>$business_key,
            'business_value'=>$business_value,
        ]);

        if (!$data) {
            return null;
        }

        return new self($data);
    }
}