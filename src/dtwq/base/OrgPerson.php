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
 * 机构人员
 * Class OrgPerson
 * @package dtwq\base
 */
class OrgPerson extends RowObject
{
    public static function getOrgPerson($person_no)
    {
        $data = DbCache::data_get('dt_org_person', $person_no);
        if ($data) {
            return new self($data);
        }
        return null;
    }

    public static function getOrgPersonName($person_no)
    {
        static $cs = [];
        if (!$person_no) {
            return '未设置';
        }

        if (isset($cs[$person_no])) {
            return $cs[$person_no];
        }

        $role = self::getOrgPerson($person_no);
        if ($role) {
            $cs[$person_no] = $role['personname'];
        } else {
            $cs[$person_no] = '未知';
        }

        return $cs[$person_no];
    }
}