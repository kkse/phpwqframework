<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/11/20
 * Time: 17:06
 */

namespace kkse\wqframework\dtwq\examine;


use think\Loader;

abstract class ABusiness
{
    protected $business_key = '';
    protected $business_name = '';
    protected $table_name = '';
    protected $pk = '';
    protected $actions = ['add','edit','delete'];
    protected $business_rule = [];

    public function __construct()
    {
        $cs = explode('\\', get_class($this));
        $this->business_key = Loader::parseName(end($cs), 0);
    }

    /**
     * @param $business_key
     * @return null|self
     */
    public static function getBusiness($business_key)
    {
        $class = __NAMESPACE__ .'\\business\\'.Loader::parseName($business_key, 1);

        if (!is_subclass_of($class, self::class)) {
            return null;
        }

        return new $class();
    }

    public static function getBusinessPairs()
    {
        $cache_key = 'examine.business_pairs';
        $pairs = S($cache_key);
        if (!$pairs) {
            $dir = __DIR__ .'/business';
            $pairs = [];
            foreach (new  \DirectoryIterator($dir) as $fi) {
                $business_key = Loader::parseName($fi->getBasename('.php'), 0);
                if (!$business_key) {
                    continue;
                }

                $business = self::getBusiness($business_key);
                if (!$business) {
                    continue;
                }

                $pairs[$business_key] = $business->business_name;
            }

            $pairs or $pairs = config('CACHE_FAIL_DATA');

            S($cache_key, $pairs);
        }

        if (!is_array($pairs)) {
            return [];
        }

        return $pairs;
    }

    public function getBusinessKey()
    {
        return $this->business_key;
    }

    public function getBusinessName()
    {
        return $this->business_name;
    }

    public function getBusinessActionName($action)
    {
        switch ($action) {
            case 'add':
                return '新增';
            case 'edit':
                return '编辑';
            case 'delete':
                return '删除';
        }

        return '未知';
    }

    //检查是否包含指定动作的业务操作
    public function hasAction($action)
    {
        return in_array($action, $this->actions);
    }

    //翻译数据
    abstract public function getExamineData($action, array $data);

    abstract public function getExamineInfo($action, Data $data);

    protected function getBusinessRule($key)
    {
        foreach ($this->business_rule as $index => $rule) {
            if ($rule[0] == $key) {
                return [$index, $rule];
            }
        }

        return [false, false];
    }

    //转换
    protected function translate($rule, $val, $data)
    {
        if (!$rule) {
            return $val;
        }

        if (is_array($rule)) {
            return isset($rule[$val])?$rule[$val]:'未知';
        }

        if (is_string($rule)) {
            if (strpos('::', $rule)) {
                return call_user_func($rule, $val)?:'未知';
            } elseif (method_exists($this, $rule)) {
                return $this->$rule($val, $data);
            }
        }
        return '未知';
    }

    protected function valIsFilter($val, $filter_val)
    {
        if ($val == $filter_val) {
            return true;
        }

        if (is_array($filter_val)) {
            if (strtoupper($filter_val[0]) == 'IN' && is_array($filter_val[1])) {
                return in_array($val, $filter_val[1]);
            }
        }

        return false;
    }

    protected function check_where($where, $data)
    {
        foreach ($where as $k => $val) {
            if (isset($data[$k])) {
                if (!$this->valIsFilter($data[$k], $val)) {
                    return false;
                }
            } else {
                return false;
            }
        }
        return true;
    }

    public function getBusinessData($action, Data $examine_data)
    {
        if ($action == 'add') {
            $data = $examine_data->getAddData();
        } else {
            $data = $examine_data->getOldData();
        }

        $business_data = [];
        $indexs = [];
        foreach ($data as $key => $val) {
            list ($index, $rule) = $this->getBusinessRule($key);
            if ($index === false) {
                continue;
            }

            if (!empty($rule[4])) {
                $where = $rule[4];
                if (!$this->check_where($where, $data)) {
                    continue;
                }
            }

            list(, $key_text, $value_rule, $value_text_rule) = $rule;
            $item = [
                'index'=>$index,
                'key'=>$key,
                'key_text'=>$key_text,
                'value'=>$this->translate($value_rule, $val, $data),
                'value_text'=>'',
            ];

            $value_text_rule and $item['value_text'] = $this->translate($value_text_rule, $val, $data);
            $business_data[] = $item;
            $indexs[] = $index;
        }

        array_multisort($indexs, SORT_ASC, $business_data);
        return $business_data;
    }

    public function getChangeData($action, Data $examine_data)
    {
        $data = $examine_data->getEditData();
        if (!$data) {
            return [];
        }

        $old_data = $examine_data->getOldData();
        $change_data = [];

        foreach ($data as $field=>$val) {
            list ($index, $rule) = $this->getBusinessRule($field);
            if ($index === false) {
                continue;
            }

            $change_data[] = [
                'key_text'=>$rule[1],
                'before_value_text'=>$old_data[$field],
                'after_value_text'=>$val,
            ];
        }
        return $change_data;
    }

    //翻译数据
    public function updateExamineData($action, array $data, Data $old)
    {
        switch ($action) {
            case 'add':
                $newdata = [
                    'pkid'=>$old->getPkid(),
                    'add'=>$data+$old->getAddData(),
                ];
                break;
            case 'edit':
                $newdata = [
                    'pkid'=>$old->getPkid(),
                    'old'=>$old->getOldData()
                ];
                $data = $data + $old->getEditData();
                $newdata['edit'] = array_diff_assoc($data, $newdata['old']);
                break;
            case 'delete'://不需要更新
            default:
                return null;
        }

        return Data::create($newdata);
    }

    /**
     * 通过后处理
     * @param FlowingWater $fw
     * @param Auditor $auditor
     */
    abstract public function adopt(FlowingWater $fw, Auditor $auditor);

    /**
     * 拒绝后处理
     * @param FlowingWater $fw
     * @param Auditor $auditor
     */
    public function refuse(FlowingWater $fw, Auditor $auditor){}



    public function getModifiedList()
    {
        return [];
    }

    public function getEditUrl(FWItem $fwitem)
    {
        assert(isset($fwitem));
        return '';
    }


}