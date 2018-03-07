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
 * 网点
 * Class Net
 * @package dtwq\base
 */
class Net extends RowObject
{

    public function getCode()
    {
        return $this->_data['netcode'];
    }

    public function getName()
    {
        return $this->_data['bankname'];
    }

    public function getOrg()
    {
        return Org::getOrg($this->_data['orgcode']);
    }

    public static function getNet($netcode)
    {
        $data = DbCache::data_get('dt_bank_net', $netcode);
        if ($data) {
            return new self($data);
        }
        return null;
    }
    public static function getNetName($netcode, $failname = '未知')
    {
        $obj = self::getNet($netcode);
        return $obj?$obj['bankname']:$failname;
    }

    public static function getOrgcode($netcode)
    {
        $obj = self::getNet($netcode);
        return $obj?$obj['orgcode']:'';
    }

    public static function getNetChildren($netcode)
    {
        $cache_key = 'net.children.list:pcode.'.$netcode;
        $list = S($cache_key);
        if (!$list) {
            $list = M('bank_net')->where(['parentcode'=>$netcode,])->column('netcode', true);
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

    public static function isSubOf($netcode, $pcode)
    {
        if ($netcode == $pcode || !$pcode) {
            return true;
        }

        if (!$netcode) {
            return false;
        }

        $net = self::getNet($netcode);
        if (!$net['parentcode']) {
            return false;
        }

        return self::isSubOf($net['parentcode'], $pcode);
    }

    public static function getAllParent($netcode, $pcode = '')
    {
        if (!$netcode) {
            return [];
        }

        if ($netcode == $pcode) {
            return [$netcode=>self::getNetName($netcode)];
        }

        $obj = self::getNet($netcode);
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

        $plist[$netcode] = $obj['bankname'];
        return $plist;
    }

}