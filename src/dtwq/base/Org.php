<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/11/2
 * Time: 22:45
 */

namespace kkse\wqframework\dtwq\base;

use Com\Internal\DbCache;
use lang\RowObject;

/**
 * 机构
 * Class Org
 * @package dtwq\base
 */
class Org extends RowObject
{
    public function getCode()
    {
        return $this->_data['orgcode'];
    }

    public function getName()
    {
        return $this->_data['name'];
    }


    public static function getOrg($orgcode)
    {
        if (!$orgcode) {
            return null;
        }
        $data = DbCache::data_get('dt_org', $orgcode);
        if ($data) {
            return new self($data);
        }
        return null;
    }

    public static function getOrgChildren($orgcode)
    {
        $cache_key = 'org.children.list:pcode.'.$orgcode;
        $list = S($cache_key);
        if (!$list) {
            $list = M('org')->where(['parentcode'=>$orgcode,])->column('orgcode');
            if (!$list) {
                $list = config('CACHE_FAIL_DATA');
            }
            S($cache_key, $list);
        }
        if (!is_array($list)) {
            return [];
        } else {
            return $list;
        }
    }

    public static function getOrgName($orgcode, $failname = '未知')
    {
        $obj = self::getOrg($orgcode);
        return $obj?$obj['name']:$failname;
    }

    public static function isSubOf($orgcode, $pcode)
    {
        if ($orgcode == $pcode || !$pcode) {
            return true;
        }

        if (!$orgcode) {
            return false;
        }

        $obj = self::getOrg($orgcode);
        if (!$obj['parentcode']) {
            return false;
        }

        return self::isSubOf($obj['parentcode'], $pcode);
    }

    public static function getAllParent($orgcode, $pcode = '')
    {
        if (!$orgcode) {
            return [];
        }

        if ($orgcode == $pcode) {
            return [$orgcode=>Org::getOrgName($orgcode)];
        }

        $obj = self::getOrg($orgcode);
        if (!$obj) {
            return [];
        }

        if ($obj['parentcode']) {
            $plist = self::getAllParent($obj['parentcode'], $pcode);
        } elseif ($pcode) {
            return [];
        } else {
            $plist = [];
        }

        $plist[$orgcode] = $obj['name'];
        return $plist;
    }

}