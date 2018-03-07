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
 * 机构部门
 * Class OrgPerson
 * @package dtwq\base
 */
class OrgDep extends RowObject
{
    public static function getOrgDep($depcode)
    {
        $data = DbCache::data_get('dt_org_dep', $depcode);
        if ($data) {
            return new self($data);
        }
        return null;
    }

    public static function getOrgDepName($depcode)
    {
        static $cs = [];
        if (!$depcode) {
            return '未设置';
        }

        if (isset($cs[$depcode])) {
            return $cs[$depcode];
        }

        $role = self::getOrgDep($depcode);
        if ($role) {
            $cs[$depcode] = $role['depname'];
        } else {
            $cs[$depcode] = '未知';
        }

        return $cs[$depcode];
    }

    public static function getPairs()
    {
        return pdo_getpairs('dt_org_dep', [], 'depcode', 'depname');
    }
}