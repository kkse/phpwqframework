<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/11/2
 * Time: 22:51
 */

namespace kkse\wqframework\dtwq\admin;

use kkse\wqframework\dtwq\base\CoreAction;
use kkse\wqframework\dtwq\system\CoreTable;
use lang\ltrait\Instance;


/**
 * 用户角色，使用类
 * Class UserRole
 * @package dtwq\admin
 */
class UserRole
{
    use Instance;

    protected $user;
    protected $role;
    protected $permission;
    protected $module_permission;

    /**
     * @var bool|self
     */
    protected $pobj = null;

    public function __construct(User $user)
    {
        $this->user = $user;
        if (!$user->isAdministrator()) {
            $this->role = Role::getRole($user->getRoleid());
            $this->permission = $this->role->getPermissionList();
            $this->module_permission = $this->role->getModulePermissionList();
            $this->initParent();
        }
    }

    protected function initParent()
    {
        $puid = $this->user->getCreatorUid();
        if (!$puid) {
            $this->pobj = false;//数据异常,有上级uid，但数据库没有数据，这种数据就没有权限
            return;
        }

        $puser = User::getUser($puid);
        if (!$puser) {
            $this->pobj = false;//数据异常,有上级uid，但数据库没有数据，这种数据就没有权限
            return;
        }

        if ($puser->isAdministrator()) {
            $this->pobj = true;//当上级是系统管理员就设置为true
            return;
        }

        $this->pobj = self::getUserRole($puser);
        $this->pobj->pobj === false and $this->pobj = false;//减少层次判断
    }

    /**
     * 根据用户获取用户角色
     * @param User $user
     * @return null|self
     */
    public static function getUserRole(User $user)
    {
        return self::autoInstance($user->getUid(), $user);
    }

    //检查当前用户是否有指定权限
    public function testPermissionAction(CoreAction $core_action, &$why = null)
    {
        if ($this->user->isAdministrator()) {//系统管理员
            return true;
        }

        if ($this->pobj === false) {//异常结构数据，没有任何访问权限
            $why = '异常结构数据，禁止访问系统';
            return false;
        }

        if (!$this->testPermissionVisit($core_action)) {
            $why = '该操作的访问权限不通过，禁止访问';
            return false;
        }

        //检查数据权限
        if ($core_action->getDataControl() && $this->testPermissionData($core_action->getDataControl())) {
            $why = '该操作的数据权限不通过，禁止访问';
            return false;
        }

        return true;
    }

    /**
     * 检查是否有访问权限
     * @param CoreAction $core_action
     * @return bool
     */
    public function testPermissionVisit(CoreAction $core_action) {
        if ($this->user->isAdministrator()) {//系统管理员
            return true;
        }

        if ($this->pobj === false) {
            return false;
        }

        //因为本来就要校验上级的访问权限，所以先判断上级，可以加快校验速度
        if ($this->pobj !== true && !$this->pobj->testPermissionVisit($core_action)) {
            return false;
        }

        if ($core_action->isModule()) {
            $check_permission = $this->module_permission;
            $check_list = [
                "{$core_action->getModule()}.{$core_action->getAction()}.{$core_action->getDo()}.{$core_action->getOp()}",
                "{$core_action->getModule()}.{$core_action->getAction()}.{$core_action->getDo()}.*",
                "{$core_action->getModule()}.{$core_action->getAction()}.*.*",
                "{$core_action->getModule()}.*.*.*",
            ];
        } else {
            $check_permission = $this->permission;
            $check_list = [
                "{$core_action->getController()}.{$core_action->getAction()}.{$core_action->getDo()}",
                "{$core_action->getController()}.{$core_action->getAction()}.*",
                "{$core_action->getController()}.*.*",
            ];
        }

        foreach ($check_list as $check_key) {
            if (in_array($check_key, $check_permission)) {
                return true;
            }
        }
        return false;
    }

    protected function testPermissionData($data_control) {
        $pk_val = get_gpc($data_control['pk']);

        if (!$pk_val) {
            return empty($data_control['must']);
        }

        if ($data_control['convert']) {
            if (is_array($data_control['convert'])) {
                $table = $data_control['convert']['table'];
                $findkey = $data_control['convert']['findkey'];
                $getkey = $data_control['convert']['getkey'];
                $pk_val = M()->table($table)->where([$findkey=>$pk_val])->value($getkey);
            } else {
                $pk_val = call_user_func($data_control['convert'], $pk_val);
            }

            if (empty($pk_val)) {
                return false;
            }
        }
        $ct = new CoreTable();
        $table = $ct->getTableInfo($data_control['table']);
        if (!$table) {
            return false;
        }

        $table_data = M($table['tablename'], null)->where([$table['pk'] => $pk_val])->find();
        if (!$table_data) {
            return empty($data_control['must']);//不是必须的话，空数据也放行
        }

        return $this->checkdata($table, $table_data);
    }

    protected function checkdata($table, $table_data)
    {
        if (!empty($table['data_control']['uid'])) {
            $uidfield = $table['data_control']['uid'];
            if (empty($this->user) || $this->user->getUid() != $table_data[$uidfield]) {
                return false;
            }
        }

        if (!empty($table['data_control']['org'])) {
            $orgfield = $table['data_control']['org'];
            $test_orgcode = $table_data[$orgfield];
            if ($test_orgcode != $this->user->getOrgcode()) {
                return false;
            }
        }

        if (!empty($table['data_control']['net'])) {
            $netfield = $table['data_control']['net'];
            $test_netcode = $table_data[$netfield];
            if ($test_netcode != $this->user->getNetcode()) {
                return false;
            }
        }
        return true;
    }

}