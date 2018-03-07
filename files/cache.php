<?php

function cache_read($key)
{
    return cache($key);
}


function cache_write($key, $value, $ttl = 0)
{
    return cache($key, $value, $ttl);
}


function cache_delete($key)
{
    return cache($key, null);
}

function cache_clean($prefix = '')
{
    global $_W;
    unset($_W['cache']);
    return cache(null);
}

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