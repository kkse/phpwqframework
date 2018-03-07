<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/11/20
 * Time: 17:32
 */

namespace kkse\wqframework\dtwq\examine;

use kkse\wqframework\dtwq\admin\User;
use kkse\wqframework\dtwq\base\Net;
use kkse\wqframework\dtwq\base\Org;

/**
 * 审核人
 * Class Auditor
 * @package dtwq\examine
 */
class Auditor
{
    protected $user = null;
    protected $type = '';
    protected $type_value = '';
    protected $name = '系统处理';
    protected $id = 0;

    public function __construct($user = null)
    {
        if ($user instanceof User) {
            if ($user->isAdministrator()) {
                $this->type = 'SYS';
                $this->type_value = 'SYS';
            } elseif ($user->isOrg()) {
                $this->type = 'ORG';
                $this->type_value = $user->getOrgcode();
            } elseif ($user->isNet()) {
                $this->type = 'NET';
                $this->type_value = $user->getNetcode();
            } else {
                $this->type = 'ERR';
                $this->type_value = '';
            }

            $this->id = $user->getUid();
            $this->name = $user->getUsername();

            $this->user = $user;
        } elseif (is_string($user)) {
            $this->name = $user;
        }
    }


    /**
     * @param $auditor_type
     * @param $auditor_id
     * @return Auditor|null
     */
    public static function loadAuthor($auditor_type, $auditor_id)
    {
        switch ($auditor_type) {
            case 'ORG':
            case 'NET':
                $check_func = $auditor_type == 'ORG' ?'isOrg':'isNet';
                $user = User::getUser($auditor_id);
                if ($user && $user->$check_func()) {
                    return new self($user);
                }
                break;
            case 'SYS'://不可能的
        }

        return null;
    }

    public function getId(){
        return $this->id;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getTypeValue()
    {
        return $this->type_value;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function getClosestList()
    {
        $idtype = $this->getType();
        $idstr = $this->getTypeValue();

        if ($idtype == 'NET') {
            $allnets = array_keys(Net::getAllParent($idstr));
            foreach ($allnets as $netcode) {
                yield $idtype => $netcode;
            }
            $idtype = 'ORG';
            $idstr = Net::getOrgcode($idstr);
        }

        if ($idtype == 'ORG') {
            $allorgs = array_keys(Org::getAllParent($idstr));
            foreach ($allorgs as $orgcode) {
                yield $idtype => $orgcode;
            }
        }
    }

    public function isUser(User $user)
    {
        if (!$user) {
            return false;
        }
        if ($user->isAdministrator()) {
            return true;
        }

        return $this->id != $user->getUid();
    }

    public function isAdmin()
    {
        return $this->user && $this->user->isAdministrator();
    }

}