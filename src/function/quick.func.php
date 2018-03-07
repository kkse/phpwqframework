<?php
//微擎后台快捷方法
use dtwq\admin\Visitor;

/**
 * 构造where条件
 * @param array $query_fields
 * @param array $gpc
 * @return array
 */
function quick_where(array $query_fields, $gpc = null)
{
    global $_GPC;
    $gpc === null and $gpc = $_GPC;

    $where = [];
    foreach ($query_fields as $field) {
        switch ($field{0}) {
            case ':'://超特殊处理
                $field = substr($field, 1);
                list($mode, $field) = explode(':', $field, 2);
                $option = null;
                if (strpos($field, ':') > 0) {
                    list($field, $option) = explode(':', $field);
                }

                switch (strtolower($mode)) {
                    case 'in':// :in:[field]:A|B|...
                        $in_list = explode('|', $option);
                        if (!empty($gpc[$field]) && in_array($gpc[$field], $in_list)) {
                            $where[$field] = $gpc[$field];
                        }
                        break;
                }

                break;
            case '=':
                $gpc_key = $field = substr($field, 1);
                $func = 'trim';
                if (strpos($field, '|') > 0) {
                    list($field, $c) = explode('|', $field);
                    $gpc_key = $field;
                    switch (strtolower($c)) {
                        case 'd':$func = 'intval';
                    }
                }

                if (strpos($field, ':') > 0) {
                    list($field, $gpc_key) = explode(':', $field);
                }

                if (!empty($gpc[$gpc_key]) || (isset($gpc[$gpc_key]) && $gpc[$gpc_key] === '0')) {
                    $where[$field] = $func($gpc[$gpc_key]);
                }
                break;
            case '%':
                $gpc_key = $field = substr($field, 1);
                if (strpos($field, ':') > 0) {
                    list($field, $gpc_key) = explode(':', $field);
                }

                empty($gpc[$gpc_key]) or $where[$field] = ['like', "%".trim($gpc[$gpc_key])."%"];
                break;
            default:
                $gpc_key = $field;
                if (strpos($field, ':') > 0) {
                    list($field, $gpc_key) = explode(':', $field);
                }
                empty($gpc[$gpc_key]) or $where[$field] = ['like', "%".trim($gpc[$gpc_key])."%"];
                break;
        }
    }
    return $where;
}

function add_rule_where($rule, &$where)
{
    global  $_GPC;

    $adminuser = Visitor::getInstance()->getUser();
    if (!$adminuser) {
        return false;
    }

    is_array($rule) or $rule = explode(',', $rule);

    foreach ($rule as $key => $val) {
        if (is_int($key)) {
            if (!is_string($val)) {
                return false;
            }
            $key = $val;
        }

        is_array($val) or $val = ['gpc'=>$key, 'field'=>$val];

        switch ($key) {
            case 'orgcode':
                $dorgcode = !empty($_GPC[$val['gpc']])?$_GPC[$val['gpc']]:'';
                $orgcode = $adminuser->isAdministrator()?$dorgcode:$adminuser->getOrgcode();
                $orgcode and $where[$val['field']] = $orgcode;
                break;
            case 'netcode':
                $dnetcode = !empty($_GPC[$val['gpc']])?$_GPC[$val['gpc']]:'';
                $netcode = $adminuser->isNet()?$adminuser->getNetcode():$dnetcode;
                $netcode and $where[$val['field']] = $netcode;
                break;
            default:
                return false;
        }
    }
    return true;
}