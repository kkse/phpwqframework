<?php
/**
 * 使用tp框架的缓存模块
 * [ECOCOM System] Copyright (c) 2014 DTROAD.COM
 * ECOCOM is NOT a free software, it under the license terms, visited http://www.dtroad.com/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

function cache_read($key)
{
    return S($key);
}


function cache_write($key, $value, $ttl = 0)
{
    return S($key, $value, $ttl);
}


function cache_delete($key)
{
    return S($key, null);
}

function cache_clean($prefix = '')
{
    global $_W;
    unset($_W['cache']);
    return S(null);
}
