<?php
/**
 * [ECOCOM System] Copyright (c) 2014 DTROAD.COM
 * ECOCOM is NOT a free software, it under the license terms, visited http://www.dtroad.com/ for more details.
 */
defined('IN_IA') or exit('Access Denied');


load()->func('cache.thinkphp');

function cache_load($key, $unserialize = false)
{
    global $_W;
    if (!empty($_W['cache'][$key])) {
        return $_W['cache'][$key];
    }

    $data = $_W['cache'][$key] = cache_read($key);
    if ($key == 'setting') {
        $_W['setting'] = $data;
        return $_W['setting'];
    } elseif ($key == 'modules') {
        $_W['modules'] = $data;
        return $_W['modules'];
    } elseif ($key == 'module_receive_enable' && empty($data)) {
        cache_build_module_subscribe_type();
        return cache_read($key);
    } else {
        return $unserialize ? iunserializer($data) : $data;
    }
}
