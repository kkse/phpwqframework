<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/11/2
 * Time: 22:45
 */

namespace kkse\wqframework\dtwq\admin;

use Com\Internal\DbCache;
use lang\RowObject;

/**
 * $obj = Role::getRole(1);
 * var_dump($obj['name']);
 * 角色
 * Class Role
 * @package dtwq\admin
 */
class Role extends RowObject
{

    public function getPermissionList()
    {
        return explode('|', $this->_data['permission']);
    }

    public function getModulePermissionList()
    {
        return explode('|', $this->_data['module_permission']);
    }

    public static function getRole($role_id)
    {
        $data = DbCache::data_get(tablename('users_role', false), $role_id);
        if ($data) {
            return new self($data);
        }
        return null;
    }

    public static function getRoleName($role_id)
    {
        static $cs = [];
        if (!$role_id) {
            return '未设置';
        }

        if (isset($cs[$role_id])) {
            return $cs[$role_id];
        }

        $role = self::getRole($role_id);
        if ($role) {
            $cs[$role_id] = $role['name'];
        } else {
            $cs[$role_id] = '未知';
        }

        return $cs[$role_id];
    }
}