<?php
namespace dtwq\examine;

use kkse\wqframework\dtwq\admin\User;

/**
 * 审核流水对象
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/11/17
 * Time: 12:01
 */
class FlowingWater
{
    /**
     * 业务处理对象
     * @var ABusiness
     */
    protected $business;

    /**
     * 业务处理动作
     * @var string
     */
    protected $action;

    /**
     * 业务处理数据
     * @var Data
     */
    protected $data;

    /**
     * 审核提交者
     * @var Author
     */
    protected $author;

    /**
     * 审核流水表对象
     * @var FWItem
     */
    protected $fw_item;

    protected $is_end = false;

    protected function __construct()
    {}

    public static function createInstance(ABusiness $business, $action, Data $data, Author $author)
    {
        //检查业务是否拥有动作
        if (!$business->hasAction($action)) {
            return null;
        }
        $_this = new self();
        $_this->business = $business;
        $_this->action = $action;
        $_this->data = $data;
        $_this->author = $author;

        return $_this;
    }

    public static function loadInstance(FWItem $fw_item)
    {
        $business = $fw_item->getBusiness();
        $action = $fw_item->getBusinessAction();
        $data = $fw_item->getExamineData(true);
        $author = $fw_item->getAuthor();

        if (!$business || !$data || !$author) {
            return null;
        }

        $obj = self::createInstance($business, $action,  $data, $author);
        if (!$obj) {
            return null;
        }

        $obj->fw_item = $fw_item;
        return $obj;
    }

    /**
     * @return ABusiness
     */
    public function getBusiness()
    {
        return $this->business;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return Data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return Author
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @return FwItem
     */
    public function getFwItem()
    {
        return $this->fw_item;
    }

    //是否不需要审核，就直接通过。
    public function isEmptyAuthor()
    {
        $rule = Rule::match($this->business, $this->action, $this->author);
        if (!$rule || !$rule->getExamineDepcodes()) {
            return true;
        }

        return false;
    }


    //======================以下是操作===========================

    //预设处理
    public function presetProcessing(&$why)
    {
        //
        if ($this->author->isAdmin() || $this->isEmptyAuthor()) {
            try {
                $this->business->adopt($this, new Auditor());
                $this->is_end = true;
                return true;
            } catch (\Exception $e) {
                $why = $e->getMessage();
                return false;
            }
        }

        if (!$this->init($why)) {//创建审核流水
            return false;
        }

        return $this->submitExamine($why);//并提交审核
    }

    //是否已经结束
    public function isEnd()
    {
        return $this->is_end;
    }

    public function init(&$why)
    {
        if ($this->fw_item) {
            $why = '已存在审核流水，不能再创建了';
            return false;
        }

        $hasFWItem = FWItem::findInstance($this->business->getBusinessKey(), $this->data->getPkid());
        if ($hasFWItem) {
            $why = '已存在审核流水，不能再创建了.';
            return false;
        }

        $data = [
            'business_key'=>$this->business->getBusinessKey(),
            'business_value'=>$this->data->getPkid(),
            'business_action'=>$this->action,
            'author_id'=>$this->author->getId(),
            'author_type'=>$this->author->getType(),
            'author_type_value'=>$this->author->getTypeValue(),
            'examined_num'=>0,
            'examine_depcodes'=>'',
            'current_depcode'=>'',
            'examine_status'=>'W',
            'examine_data'=>$this->data->__toString(),
            'refusal_reason'=>'',
            'refuse_auditor'=>'',
            'pass_auditors'=>'',
            'add_time'=>date('Y-m-d H:i:s'),
            'update_time'=>date('Y-m-d H:i:s'),
        ];

        $rule = Rule::match($this->business, $this->action, $this->author);
        if ($rule) {
            //$data['examined_num'] = $rule->getExamineNum();
            $data['examine_depcodes'] = $rule->getExamineDepcodes();
        }

        $this->fw_item = FWItem::createInstance($data);
        if (!$this->fw_item) {
            $why = '创建审核流水失败';
            return false;
        }
        return true;
    }

    //添加审核
    public function submitExamine(&$why)
    {
        if (!$this->fw_item) {
            $why = '不存在审核流水,无法提交。';
            return false;
        }

        if ($this->fw_item->getExamineStatus() != 'W') {
            $why = '审核流水状态不是待提交状态,无法提交。';
            return false;
        }

        if ($this->fw_item->getExamineNum() > 0) {
            if (!$this->fw_item->submit()) {
                $why = '提交审核流水，改变状态失败。';
                return false;
            }

            return true;
        }

        //不需要审核，直接通过。
        if (!$this->fw_item->backup()) {
            $why = '备份审核流水失败。';
            return false;
        }

        try {
            $this->business->adopt($this, new Auditor());
            $this->is_end = true;
        } catch (\Exception $e) {
            $why = $e->getMessage();
            return false;
        }
        return true;
    }

    //编辑审核业务内容
    public function updateExamineData($data, &$why)
    {
        if (!$this->fw_item) {
            $why = '没有审核流水不可更新审核数据';
            return false;
        }

        $data = $this->business->updateExamineData($this->action, $data, $this->data);
        if (!$data) {
            $why = '更新审核数据异常';
            return false;
        }

        $ret = $this->fw_item->updateData($data);
        if (!$ret) {
            $why = '更新审核数据异常';
            return false;
        }

        $ret = $this->fw_item->submit();
        if (!$ret) {
            $why = '重新提交审核失败';
            return false;
        }

        return true;
    }

    //撤销审核
    public function revokeExamine()
    {
        if (!$this->fw_item) {
            $this->_throw('没有审核流水记录');
            //return false;//是true还是false，待定
        }

        $ret = $this->fw_item->update([
            'examine_status'=>'R',
        ], ['examine_status'=>'P']);

        $ret or $this->_throw('撤销失败');
    }

    //关闭审核
    public function closeExamine()
    {
        if (!$this->fw_item) {
            $this->_throw('没有审核流水记录');
            //是true还是false，待定
        }

        if ($this->fw_item->getExamineStatus() == 'P') {
            $this->_throw('不能关闭审核中的审核流水记录');
            //不能关闭审核中的记录
        }

        $this->fw_item->backup() or $this->_throw('备份失败');
        $this->business->refuse($this, new Auditor());
        $this->is_end = true;
    }

    public function checkAuditDep(User $auditor)
    {
        if (!$this->fw_item) {
            return false;//是true还是false，待定
        }

        foreach ($this->fw_item->getCurrentDepcode(true) as $current_depcode) {
            if ($auditor->getDepcode()==$current_depcode) {
                return true;
            }
        }
        return false;
    }

    //审核通过
    public function auditPassed(User $user)
    {
        $this->fw_item or $this->_throw('尚未创建数据库审核流水，不能审核');

        if ($user->isAdministrator()) {
            $next_depcode = '';
            $next_index = 0;
        } else {
            $this->checkAuditDep($user) or $this->_throw(sprintf('审核人[%s]不能审核改数据', $user->getUsername()));
            $next_depcode = $this->fw_item->getNextDepcode();
            $next_index = $this->fw_item->getExaminedNum()+1;
        }

        $pass_auditors = $this->fw_item->getPassAuditors();
        $pass_auditors and $pass_auditors .= ',';
        $pass_auditors .= $user->getUsername();

        $update = [
            'pass_auditors'=>$pass_auditors,
            'current_depcode'=>$next_depcode,
            'examined_num'=>$next_index,
        ];

        $next_depcode or $update['examine_status'] = 'S';

        if (!$this->fw_item->update($update, ['examine_status'=>'P', 'examined_num'=>$this->fw_item->getExaminedNum()])) {
            $this->_throw('更新失败');
        }

        if ($this->fw_item->getExamineStatus() == 'S') {//已经结束了
            $this->fw_item->backup() or $this->_throw('备份失败');
            $this->business->adopt($this, new Auditor($user));
            $this->is_end = true;
        }
    }

    //审核拒绝
    public function auditRefuse(User $user, $refusal_reason)
    {
        $this->fw_item or $this->_throw('尚未创建数据库审核流水，不能审核');
        $this->fw_item->getExamineStatus() == 'P' or $this->_throw('审核流水不是审核中，不能审核');

        ($user->isAdministrator() || $this->checkAuditDep($user))
        or $this->_throw(sprintf('审核人[%s]不能审核改数据', $user->getUsername()));

        if (!$this->fw_item->update([
            'refusal_reason'=>$refusal_reason,
            'refuse_auditor'=>$user->getUsername(),
            'examine_status'=>'F',
        ], ['examine_status'=>'P'])) {
            $this->_throw('更新失败');
        }

        $auditor = new Auditor($user);
        $this->business->refuse($this, $auditor);
    }

    protected function _throw($msg)
    {
        throw new \Exception($msg);
    }

}