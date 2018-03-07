<?php
/**
 * [ECOCOM System] Copyright (c) 2014 DTROAD.COM
 * ECOCOM is NOT a free software, it under the license terms, visited http://www.dtroad.com/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

function cache_redis()
{
    static $redisobj;
    if (!extension_loaded('redis')) {
        return error(1, 'Class redis is not found');
    }
    if (empty($redisobj)) {
        $redisobj = \Com\Internal\CacheDbSelect::genInstance(4);
    }
    return $redisobj;
}


function cache_read($key)
{
    $redis = cache_redis();
    if (is_error($redis)) {
        return $redis;
    }
    $result = $redis->get($key);
    $result and $result = json_decode($result, true);
    return $result;
}


function cache_write($key, $value, $ttl = 0)
{
    $redis = cache_redis();
    if (is_error($redis)) {
        return $redis;
    }

    if ($ttl > 0) {
        return $redis->setex($key, $ttl, json_encode($value));
    } else {
        return $redis->set($key, json_encode($value));
    }
}


function cache_delete($key)
{
    $redis = cache_redis();
    if (is_error($redis)) {
        return $redis;
    }
    return $redis->delete($key);
}

function cache_clean($prefix = '')
{
    global $_W;
    $redis = cache_redis();
    if (is_error($redis)) {
        return $redis;
    }

    $keys = $redis->getKeys($prefix. '*');
    foreach ($keys as $key) {
        $redis->delete($key);
    }

    if (!$prefix) {
        unset($_W['cache']);
    }
    return true;
}
