<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/11/23
 * Time: 11:32
 */

namespace kkse\wqframework\dtwq\examine;

use lang\RowObject;
use think\db\Query;

/**
 * 审核规则定义
 * Class Rule
 * @package dtwq\examine
 */
class Rule extends RowObject
{
    protected $business_key;
    protected $business_action;
    protected $idtype;
    protected $idstr;
    protected $level;
    protected $examine_num;
    protected $examine_depcodes;

    /**
     * @return mixed
     */
    public function getBusinessKey()
    {
        return $this->business_key;
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
    public function getIdtype()
    {
        return $this->idtype;
    }

    /**
     * @return mixed
     */
    public function getIdstr()
    {
        return $this->idstr;
    }

    /**
     * @return mixed
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @return mixed
     */
    public function getExamineNum()
    {
        return $this->examine_num;
    }

    /**
     * @return mixed
     */
    public function getExamineDepcodes()
    {
        return $this->examine_depcodes;
    }




    /**
     * 匹配规则
     * @param ABusiness $business
     * @param $action
     * @param Author $author
     * @return Rule|null
     */
    public static function match(ABusiness $business, $action, Author $author)
    {
        $list = [];

        foreach ($author->getClosestList() as $idtype => $idstr) {
            $list[] = [
                'business_key'=>['in', ['TOT', $business->getBusinessKey()]],
                'business_action'=>['in', ['TOT', $action]],
                'idtype'=>$idtype,
                'idstr'=>$idstr,
            ];
        }

        if (!$list) {
            return null;
        }

        $where = function(Query $query) use ($list) {
            foreach ($list as $item) {
                $query->whereOr($item);
            }
        };

        $row = M()->table(config('DB_PREFIX').'examine_definition')
            ->where($where)
            ->order('level DESC')
            ->find();

        if (!$row) {
            return null;
        }

        return new self($row);
    }

}