<?php
namespace kkse\wqframework\dtwq\admin;

//后台管理员类,只用于获取信息和解析信息
use Com\Internal\DbCache;
use kkse\wqframework\dtwq\base\Net;
use kkse\wqframework\dtwq\base\Org;
use lang\RowObject;

class User extends RowObject
{
    const TABLE_NAME = 'users';
    protected $cd = [];//缓存数据

    public function __construct(array $data)
    {
        parent::__construct($data);

        //初始化一些数据
        $this->_data['name'] = $this->_data['username'];
        $this->_data['clerk_id'] = $this->_data['uid'];
        $this->_data['store_id'] = 0;
        $this->_data['clerk_type'] = '2';

    }

    protected function update(array $db_update, array $cache_update = [])
    {
        if (!$this->getUid()) {
            return false;
        }

        $update = $cache_update;
        if ($db_update) {
            pdo_update(self::TABLE_NAME, $db_update, ['uid' => $this->getUid()])
            and $update = $db_update + $update;
        }

        if ($update) {
            DbCache::cache_update('dti_users', $this->getUid(), $update, !empty($cache_update));
        }
        return true;
    }

    public function checkHash($hash)
    {
        return isset($this->_data['hash']) && $this->_data['hash'] == $hash;
    }

    public function resetHash()
    {
        $hash = $this->isAdministrator()?'admin':mt_rand();
        $this->update([], ['hash' => $hash]);
        return $hash;
    }


    public function getUid()
    {
        return $this->_data['uid'];
    }

    public function getRoleid()
    {
        return $this->_data['role_id'];
    }

    public function getMenuId()
    {
        return $this->_data['menu_id'];
    }

    public function getCreatorUid()
    {
        return $this->_data['creator_uid'];
    }

    public function getUsername()
    {
        return $this->_data['username'];
    }

    public function isBin()
    {
        return !$this->isAdministrator() &&  $this->_data['status'] == 1;
    }

    public function getDepcode()
    {
        return $this->_data['depcode'];
    }

    /**
     * 是否是系统超级管理员
     * @return mixed
     */
    public function isAdministrator()
    {
        return $this->_data['idtype'] == 'SYS';
    }

    /**
     * 是否是机构管理员
     * @return bool
     */
    public function isOrg()
    {
        return $this->_data['idtype'] == 'ORG';
    }

    /**
     * 是否是网点管理员
     * @return bool
     */
    public function isNet()
    {
        return $this->_data['idtype'] == 'NET';
    }

    public function getNetcode()
    {
        if (isset($this->cd['netcode'])) {
            return $this->cd['netcode'];
        }
        switch ($this->_data['idtype']) {
            case 'SYS':
                $this->cd['netcode'] = 'SYS';
                break;
            case 'ORG':
                $this->cd['netcode'] = 'ORG';
                break;
            case 'NET':
                $this->cd['netcode'] = $this->_data['idstr'];
                break;
            default:
                $this->cd['netcode'] =  false;
                break;
        }

        return $this->cd['netcode'];
    }

    /**
     * 获取机构代码
     * @return mixed
     */
    public function getOrgcode()
    {
        if (isset($this->cd['orgcode'])) {
            return $this->cd['orgcode'];
        }
        switch ($this->_data['idtype']) {
            case 'SYS':
                $this->cd['orgcode'] = 'SYS';
                break;
            case 'ORG':
                $this->cd['orgcode'] = $this->_data['idstr'];
                break;
            case 'NET':
                if (!isset($this->cd['mynet'])) {
                    $this->cd['mynet'] = M('dt_bank_net', null)->where(['netcode'=>$this->_data['idstr']])->find();
                }
                //查找机构号
                $this->cd['orgcode'] = $this->cd['mynet']['orgcode'];
                break;
            default:
                $this->cd['orgcode'] =  false;
                break;
        }

        return $this->cd['orgcode'];
    }

    public function checkEffective()
    {
        if ($this->isAdministrator()) {
            return true;
        }

        if ($this->isOrg()) {
            return !!$this->getOrgcode();
        }

        if ($this->isNet()) {
            return !!$this->getNetcode();
        }

        return false;
    }

    public function getRule($data, $rule = 'orgcode,netcode')
    {
        is_array($rule) or $rule = explode(',', $rule);

        $where = [];

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
                    $dorgcode = !empty($data[$val['gpc']])?$data[$val['gpc']]:'';
                    $orgcode = $this->isAdministrator()?$dorgcode:$this->getOrgcode();

                    $where[$val['field']] = $orgcode;
                    break;
                case 'netcode':
                    $dnetcode = !empty($data[$val['gpc']])?$data[$val['gpc']]:'';
                    $netcode = $this->isNet()?$this->getNetcode():$dnetcode;

                    $where[$val['field']] = $netcode;
                    break;
                default:
                    return false;
            }
        }
        return $where;
    }


    public function checkPwd($pwd)
    {
        if (!$this->_data['salt']) {
            return password_verify($pwd, $this->_data['password']);
        }
        return false;
    }

    public function setPwd($pwd)
    {
        $data = [];
        //$data['salt'] = random(8);
        //$data['password'] = user_hash($pwd, $data['salt']);
        $data['salt'] = '';
        $data['password'] = password_hash($pwd, PASSWORD_DEFAULT);
        return $this->update($data);
    }

    public function recordLast(array $status)
    {
        return $this->update([
            'lastvisit'=>$status['lastvisit'],
            'lastip'=>$status['lastip'],
        ]);
    }

    public static function getUser($uid)
    {
        $uid = intval($uid);
        if (!$uid) {
            return null;
        }
        $data = DbCache::data_get(tablename('users', false), $uid);
        if (is_array($data)) {
            return new User($data);
        }
        return null;
    }

    public static function info($user)
    {
        switch ($user['idtype']) {
            case 'SYS':
                return '系统管理员角色';
            case 'ORG':
                return '机构:'.Org::getOrgName($user['idstr']);
            case 'NET':
                return '网点:'.Net::getNetName($user['idstr']);
            default:
                return '未知';
        }
    }

    public static function convertUserName($uid)
    {
        $user = self::getUser($uid);
        if ($user) {
            return $user->getUsername();
        }
        return '';
    }
}