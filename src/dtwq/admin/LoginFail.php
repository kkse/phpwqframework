<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/10/31
 * Time: 16:43
 */

namespace kkse\wqframework\dtwq\admin;
use Org\connection\Factory;

/**
 * 登录失败控制类
 * Class LoginFail
 * @package dtwq\admin
 */
class LoginFail
{
    const REDIS_REDISKEY = 'redis';//缓存数据库
    const REDIS_DB_SELECT = 3;//缓存数据库
    const FAIL_COUNT = 5;//只能失败5次
    const FAIL_EXPIRE = 86400;//保存1天

    protected $_redis;


    public function __construct()
    {
        $this->_redis = Factory::getRedis(self::REDIS_REDISKEY, self::REDIS_DB_SELECT);
    }

    /**
     * 检查登录账号能不能登录
     * @param $username
     * @param null $why
     * @return bool
     */
    public function check($username, &$why = null)
    {
        $cache_key = $this->getCacheKey($username);
        if ($this->_redis->get($cache_key) >= self::FAIL_COUNT) {
            $why = '当日输入密码错误次数超过5次,已锁定,请于24小时后登录或联系客服解锁';
            return false;
        }
        return true;
    }

    public function addFail($username)
    {
        $cache_key = $this->getCacheKey($username);
        $this->_redis->incr($cache_key);
        $this->_redis->expire($cache_key, self::FAIL_EXPIRE);
    }

    public function delFail($username)
    {
        $cache_key = $this->getCacheKey($username);
        return $this->_redis->delete($cache_key);
    }

    protected function getCacheKey($username)
    {
        $cache_key = 'loginfail:'.bin2hex($username);
        return $cache_key;
    }

}