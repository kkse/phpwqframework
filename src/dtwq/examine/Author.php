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
use Org\Businessman\BusinessmanAuth;
use Org\Base\Factory as BaseFactory;

/**
 * 审核提交者
 * Class Author
 * @package dtwq\examine
 */
class Author
{
    protected $obj;
    protected $type;
    protected $type_value;
    protected $name;
    protected $id;

    public function __construct($obj)
    {
        if ($obj instanceof User) {
            if ($obj->isAdministrator()) {
                $this->type = 'SYS';
                $this->type_value = 'SYS';
            } elseif ($obj->isOrg()) {
                $this->type = 'ORG';
                $this->type_value = $obj->getOrgcode();
            } elseif ($obj->isNet()) {
                $this->type = 'NET';
                $this->type_value = $obj->getNetcode();
            } else {
                $this->type = 'ERR';
                $this->type_value = '';
            }

            $this->id = $obj->getUid();
            $this->name = $obj->getUsername();
        }

        elseif ($obj instanceof BusinessmanAuth) {
            $this->type = 'MER';
            $this->type_value = $obj->getMercode();
            $this->id = $obj->getId();
            $this->name = $obj->getAccount();
        }

        $this->obj = $obj;
    }

    /**
     * @param $author_type
     * @param $author_id
     * @return Author|null
     */
    public static function loadAuthor($author_type, $author_id)
    {
        switch ($author_type) {
            case 'ORG':
            case 'NET':
                $check_func = $author_type == 'ORG' ?'isOrg':'isNet';
                $user = User::getUser($author_id);
                if ($user && $user->$check_func()) {
                    return new self($user);
                }
                break;
            case 'MER':
                $muser = BaseFactory::getMerchantUser($author_id);
                if ($muser) {
                    return new self($muser);
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
     * @return User|BusinessmanAuth
     */
    public function getObj()
    {
        return $this->obj;
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

    public function getTypeInfo()
    {
        switch ($this->type) {
            case 'ORG':
                return '机构管理员:'.Org::getOrgName($this->type_value, $this->type_value);
            case 'NET':
                return '网点管理员:'.Net::getNetName($this->type_value, $this->type_value);
            case 'MER':
                $mer = BaseFactory::getMerchant($this->type_value);
                if ($mer) {
                    return '商户:'.$mer->getMerName();
                } else {
                    return '商户:'.$this->type_value;
                }
                break;
            case 'SYS'://不可能的
        }

        return '未知';
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

        if ($idtype == 'MER') {
            yield $idtype => $idstr;
            $mer = BaseFactory::getMerchant($idstr);
            if ($mer) {
                $netcode = $mer->getNetcode();
                $orgcode = $mer->getOrgcode();

                if ($netcode) {
                    $idtype = 'NET';
                    $idstr = $netcode;
                } elseif ($orgcode) {
                    $idtype = 'ORG';
                    $idstr = $orgcode;
                }
            }
        }

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

    public function isUser($user)
    {
        if (!$user) {
            return false;
        }
        if ($user instanceof User) {
            if ($user->isAdministrator()) {
                return true;
            }

            return $this->obj instanceof User && $this->id == $user->getUid();
        }elseif ($user instanceof BusinessmanAuth) {
            return $this->obj instanceof BusinessmanAuth && $this->id == $user->getId();
        }

        return false;
    }

    public function isAdmin()
    {
        return $this->obj instanceof User && $this->obj->isAdministrator();
    }

}