<?php
/**
 * [ECOCOM System] Copyright (c) 2014 DTROAD.COM
 * ECOCOM is NOT a free software, it under the license terms, visited http://www.dtroad.com/ for more details.
 */
defined('IN_IA') or exit('Access Denied');


function cache_read($key)
{
    $where = ['key' => $key];
    $val = pdo_getcolumn('core_cache', $where, 'value');
    return iunserializer($val);
}


function cache_write($key, $data)
{
    if (empty($key) || !isset($data)) {
        return false;
    }
    $record = array();
    $record['key'] = $key;
    $record['value'] = iserializer($data);
    return pdo_insert('core_cache', $record, true);
}


function cache_delete($key)
{
    return pdo_delete('core_cache', ['key' => $key]);
}


function cache_clean($prefix = '')
{
    global $_W;
    if (empty($prefix)) {
        $result = pdo_delete('core_cache');
        if ($result) {
            unset($_W['cache']);
        }
    } else {
        $result = pdo_delete('core_cache', ['key' => ['like' => "{$prefix}:%"]]);
    }
    return $result;
}
