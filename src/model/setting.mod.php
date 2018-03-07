<?php
/**
 * [Dtroad System] Copyright (c) 2014 DTROAD.COM
 * Dtroad is NOT a free software, it under the license terms, visited http://www.dtroad.com/ for more details.
 */
defined('IN_IA') or exit('Access Denied');


function setting_save($data = '', $key = '') {
	if (empty($data) && empty($key)) {
		return FALSE;
	}
	if (is_array($data) && empty($key)) {
		foreach ($data as $key => $value) {
			$record[] = "('$key', '" . iserializer($value) . "')";
		}
		if ($record) {
			$return = pdo_query("REPLACE INTO " . tablename('core_settings') . " (`key`, `value`) VALUES " . implode(',', $record));
		}
	} else {
		$record = array();
		$record['key'] = $key;
		$record['value'] = iserializer($data);
		$return = pdo_insert('core_settings', $record, TRUE);
	}
	$cachekey = "setting";
	cache_write($cachekey, '');
	return $return;
}


function setting_load($key = '') {
	global $_W;
	$cachekey = "setting";
	$settings = cache_load($cachekey);
	if (empty($settings)) {
        $settings = pdo_getpairs('core_settings', 1, 'key', 'value');
        $settings or $settings = [];
		if (is_array($settings)) {
			foreach ($settings as $k => $v) {
				$settings[$k] = iunserializer($v);
			}
		}
		cache_write($cachekey, $settings);
	}
	if (!is_array($_W['setting'])) {
		$_W['setting'] = array();
	}
	$_W['setting'] = array_merge($_W['setting'], $settings);
	return $settings;
}

