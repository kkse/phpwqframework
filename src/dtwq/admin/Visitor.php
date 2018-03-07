<?php
namespace kkse\wqframework\dtwq\admin;

//会员访问类
use Com\Internal\DbCache;

final class Visitor
{
    const SESSION_KEY = 'session_admin_user';

    /**
     * @var User
     */
    protected $_user = null;
    public function __construct($uid, $hash, $setAuth)
    {
        $this->setAuth = $setAuth;
        $this->setUid($uid, $hash);
    }

    public static function getInstance()
    {
        static $obj = null;
        if ($obj) {
            return $obj;
        }

        $session = session(self::SESSION_KEY);
        if ($session && isset($session['uid'], $session['hash'])) {
            $obj = new self(intval($session['uid']), $session['hash'], true);
        } else {
            $obj = new self(0, false, true);
        }

        return $obj;
    }

    protected static function user_hash($passwordinput, $salt) {
        $passwordinput = "{$passwordinput}-{$salt}-".config('AUTHKEY');
        return sha1($passwordinput);
    }

    /**
     * @param $username
     * @param $password
     * @return User|null
     */
    public static function find($username, $password)
    {
        //$password 是原文

        $record = pdo_get('users', ['username' => $username]);
        if ($record['salt']) {//旧的
            $password_hash = self::user_hash($password, $record['salt']);
            if ($password_hash != $record['password']) {
                return null;
            }

        } else {
            if (!password_verify($password , $record['password'])) {
                return null;
            }
        }
        $user = new User($record);
        if ($record['salt']) {//旧的
            $user->setPwd($password);//重新设置新的密码
        }
        return $user;
    }

    public function injectionGlobal()
    {
        global $_W;
        if ($user = $this->getUser()) {
            $_W['isfounder'] = $user->isAdministrator();
            $_W['uid'] = $user->getUid();
            $_W['username'] = $user->getUsername();
            $_W['user'] = $user->toArray();
        }
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->_user;
    }

    protected function setAuthInfo($authinfo)
    {
        if ($this->setAuth) {
            session(self::SESSION_KEY, $authinfo);
            session('ascription_set', null);
        }
        return $this;
    }

    protected static function getAuthInfo()
    {
        return session(self::SESSION_KEY);
    }


    public function setUid($uid, $random = false)
    {
        $this->setUser(User::getUser($uid), $random);
        return $this;
    }

    /**
     * @param User|null $user
     * @param bool $random
     * @return $this
     */
    public function setUser(User $user = null, $random = false)
    {
        if ($this->_user) {
            if ($user) {
                if ($this->_user->getUid() == $user->getUid()) {
                    if (is_bool($random) || $this->_user->checkHash($random)) {
                        return $this;
                    }
                    $user = null;
                }
            }
            $this->_user = null;
        } elseif ($user && !is_bool($random)) {
            if (!$user->checkHash($random)) {
                $user = null;
            }
        }

        $this->_user = $user;

        if ($this->setAuth) {
            if ($user) {
                if (is_bool($random)) {
                    if ($random) {
                        $this->setAuthInfo(['uid'=>$this->_user->getUid(), 'hash'=>$random]);
                    } else {
                        if ($random = $this->_user->resetHash()) {
                            $authinfo = ['uid'=>$this->_user->getUid(), 'hash'=>$random];
                            $this->setAuthInfo($authinfo);
                        }
                    }
                } else {
                    $this->_user->checkHash($random) or $this->_user = null;
                }
            } else {
                $this->setAuthInfo(null);
            }
        }

        return $this;
    }

    public function logout()
    {
        if ($this->_user) {
            $this->setUser(null);
        }
    }

    public function login(User $user)
    {
        $status = [
            'lastvisit'=>TIMESTAMP,
            'lastip'=>CLIENT_IP,
        ];

        if ($user && $user->recordLast($status)) {
            $this->setUser($user);
            return true;
        }
        return false;
    }

}
