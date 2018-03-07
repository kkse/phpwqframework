<?php
/**
 * [ECOCOM System] Copyright (c) 2014 DTROAD.COM
 * ECOCOM is NOT a free software, it under the license terms, visited http://www.dtroad.com/ for more details.
 */
defined('IN_IA') or exit('Access Denied');


function ext_module_convert($manifest) {
	return array(
		'name' => $manifest['application']['identifie'],
		'title' => $manifest['application']['name'],
		'version' => $manifest['application']['version'],
		'type' => $manifest['application']['type'],
		'ability' => $manifest['application']['ability'],
		'description' => $manifest['application']['description'],
		'author' => $manifest['application']['author'],
		'url' => $manifest['application']['url'],
		'settings'  => intval($manifest['application']['setting']),
		'subscribes' => iserializer(is_array($manifest['platform']['subscribes']) ? $manifest['platform']['subscribes'] : array()),
		'handles' => iserializer(is_array($manifest['platform']['handles']) ? $manifest['platform']['handles'] : array()),
		'isrulefields' => intval($manifest['platform']['isrulefields']),
		'iscard' => intval($manifest['platform']['iscard']),
		'cover' => $manifest['bindings']['cover'],
		'rule' => $manifest['bindings']['rule'],
		'menu' => $manifest['bindings']['menu'],
		'home' => $manifest['bindings']['home'],
		'profile' => $manifest['bindings']['profile'],
		'shortcut' => $manifest['bindings']['shortcut'],
		'function' => $manifest['bindings']['function'],
		'permissions' => $manifest['permissions'],
		'issystem' => 0,
	);
}


function ext_module_manifest_parse($xml) {
	if (!strexists($xml, '<manifest')) {
		$xml = base64_decode($xml);
	}
	if (empty($xml)) {
		return array();
	}
	$dom = new DOMDocument();
	$dom->loadXML($xml);
		$root = $dom->getElementsByTagName('manifest')->item(0);
	if (empty($root)) {
		return array();
	}
	$vcode = explode(',', $root->getAttribute('versionCode'));
	$manifest['versions'] = array();
	if (is_array($vcode)) {
		foreach ($vcode as $v) {
			$v = trim($v);
			if (!empty($v)) {
				$manifest['versions'][] = $v;
			}
		}
		$manifest['versions'][] = '0.52';
		$manifest['versions'][] = '0.6';
		$manifest['versions'] = array_unique($manifest['versions']);
	}
	$manifest['install'] = $root->getElementsByTagName('install')->item(0)->textContent;
	$manifest['uninstall'] = $root->getElementsByTagName('uninstall')->item(0)->textContent;
	$manifest['upgrade'] = $root->getElementsByTagName('upgrade')->item(0)->textContent;
	$application = $root->getElementsByTagName('application')->item(0);
	$manifest['application'] = array(
		'name' => trim($application->getElementsByTagName('name')->item(0)->textContent),
		'identifie' => trim($application->getElementsByTagName('identifie')->item(0)->textContent),
		'version' => trim($application->getElementsByTagName('version')->item(0)->textContent),
		'type' => trim($application->getElementsByTagName('type')->item(0)->textContent),
		'ability' => trim($application->getElementsByTagName('ability')->item(0)->textContent),
		'description' => trim($application->getElementsByTagName('description')->item(0)->textContent),
		'author' => trim($application->getElementsByTagName('author')->item(0)->textContent),
		'url' => trim($application->getElementsByTagName('url')->item(0)->textContent),
		'setting' => trim($application->getAttribute('setting')) == 'true',
	);
	$platform = $root->getElementsByTagName('platform')->item(0);
	if (!empty($platform)) {
		$manifest['platform'] = array(
			'subscribes' => array(),
			'handles' => array(),
			'isrulefields' => false,
			'iscard' => false,
		);
		$subscribes = $platform->getElementsByTagName('subscribes')->item(0);
		if (!empty($subscribes)) {
			$messages = $subscribes->getElementsByTagName('message');
			for ($i = 0; $i < $messages->length; $i++) {
				$t = $messages->item($i)->getAttribute('type');
				if (!empty($t)) {
					$manifest['platform']['subscribes'][] = $t;
				}
			}
		}
		$handles = $platform->getElementsByTagName('handles')->item(0);
		if (!empty($handles)) {
			$messages = $handles->getElementsByTagName('message');
			for ($i = 0; $i < $messages->length; $i++) {
				$t = $messages->item($i)->getAttribute('type');
				if (!empty($t)) {
					$manifest['platform']['handles'][] = $t;
				}
			}
		}
		$rule = $platform->getElementsByTagName('rule')->item(0);
		if (!empty($rule) && $rule->getAttribute('embed') == 'true') {
			$manifest['platform']['isrulefields'] = true;
		}
		$card = $platform->getElementsByTagName('card')->item(0);
		if (!empty($card) && $card->getAttribute('embed') == 'true') {
			$manifest['platform']['iscard'] = true;
		}
	}
	$bindings = $root->getElementsByTagName('bindings')->item(0);
	if (!empty($bindings)) {
		global $points;
		if (!empty($points)) {
			$ps = array_keys($points);
			$manifest['bindings'] = array();
			foreach ($ps as $p) {
				$define = $bindings->getElementsByTagName($p)->item(0);
				$manifest['bindings'][$p] = _ext_module_manifest_entries($define);
			}
		}
	}
		$permissions = $root->getElementsByTagName('permissions')->item(0);
	if (!empty($permissions)) {
		$manifest['permissions'] = array();
		$items = $permissions->getElementsByTagName('entry');
		for ($i = 0; $i < $items->length; $i++) {
			$item = $items->item($i);
			$row = array(
				'title' => $item->getAttribute('title'),
				'permission' => $item->getAttribute('do'),
			);
			if (!empty($row['title']) && !empty($row['permission'])) {
				$manifest['permissions'][] = $row;
			}
		}
	}
	return $manifest;
}


function ext_module_manifest($modulename) {
	$filename = IA_ROOT . '/addons/' . $modulename . '/manifest.xml';
	if (!file_exists($filename)) {
		return array();
	}
	$xml = file_get_contents($filename);
	return ext_module_manifest_parse($xml);
}


function _ext_module_manifest_entries($elm) {
	$ret = array();
	if (!empty($elm)) {
		$call = $elm->getAttribute('call');
		if (!empty($call)) {
			$ret[] = array('call' => $call);
		}
		$entries = $elm->getElementsByTagName('entry');
		for ($i = 0; $i < $entries->length; $i++) {
			$entry = $entries->item($i);
			$row = array(
				'title' => $entry->getAttribute('title'),
				'do' => $entry->getAttribute('do'),
				'direct' => $entry->getAttribute('direct') == 'true',
				'state' => $entry->getAttribute('state')
			);
			if (!empty($row['title']) && !empty($row['do'])) {
				$ret[] = $row;
			}
		}
	}
	return $ret;
}


function ext_module_checkupdate($modulename) {
	$manifest = ext_module_manifest($modulename);
	if (!empty($manifest) && is_array($manifest)) {
		$version = $manifest['application']['version'];
		load()->model('module');
		$module = module_fetch($modulename);
		if (ver_compare($version, $module['version']) == '1') {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}


function ext_module_clean($modulename, $isCleanRule = false) {
	$pars = array();
	$pars[':module'] = $modulename;
	$sql = 'DELETE FROM ' . tablename('core_queue') . ' WHERE `module`=:module';
	pdo_query($sql, $pars);

	$sql = 'DELETE FROM ' . tablename('modules') . ' WHERE `name`=:module';
	pdo_query($sql, $pars);

	$sql = 'DELETE FROM ' . tablename('modules_bindings') . ' WHERE `module`=:module';
	pdo_query($sql, $pars);

	if ($isCleanRule) {
		$sql = 'DELETE FROM ' . tablename('stat_rule') . ' WHERE `rid` IN (SELECT `id` FROM ' . tablename('rule') . ' WHERE `module`=:module)';
		pdo_query($sql, $pars);

		$sql = 'DELETE FROM ' . tablename('stat_keyword') . ' WHERE `rid` IN (SELECT `id` FROM ' . tablename('rule') . ' WHERE `module`=:module)';
		pdo_query($sql, $pars);

		$sql = 'DELETE FROM ' . tablename('rule') . ' WHERE `module`=:module';
		pdo_query($sql, $pars);

		$sql = 'DELETE FROM ' . tablename('rule_keyword') . ' WHERE `module`=:module';
		pdo_query($sql, $pars);

		$sql = 'DELETE FROM ' . tablename('stat_msg_history') . ' WHERE `module`=:module';
		pdo_query($sql, $pars);

		$sql = 'SELECT rid FROM ' . tablename('cover_reply') . ' WHERE `module`=:module';
		$data = pdo_fetchall($sql, $pars, 'rid');
		if (!empty($data)) {
			$rids = array_keys($data);
			$ridstr = implode(',', $rids);
			pdo_query('DELETE FROM ' . tablename('rule_keyword') . " WHERE module = 'cover' AND rid IN ({$ridstr})");
			pdo_query('DELETE FROM ' . tablename('rule') . " WHERE module = 'cover' AND id IN ({$ridstr})");

			$sql = 'DELETE FROM ' . tablename('cover_reply') . ' WHERE `module`=:module';
			pdo_query($sql, $pars);
		}
	}

	$sql = 'DELETE FROM ' . tablename('site_nav') . ' WHERE `module`=:module';
	pdo_query($sql, $pars);

	$sql = 'DELETE FROM ' . tablename('uni_account_modules') . ' WHERE `module`=:module';
	pdo_query($sql, $pars);

}


function ext_module_manifest_validate() {
    $xsd = <<<TPL
<?xml version="1.0" encoding="utf-8"?>
<xs:schema xmlns='http://www.dtroad.com' targetNamespace='http://www.dtroad.com' xmlns:xs="http://www.w3.org/2001/XMLSchema" elementFormDefault="qualified">
	<xs:element name="entry">
		<xs:complexType>
			<xs:attribute name="title" type="xs:string" />
			<xs:attribute name="do" type="xs:string" />
			<xs:attribute name="direct" type="xs:boolean" />
			<xs:attribute name="state" type="xs:string" />
		</xs:complexType>
	</xs:element>
	<xs:element name="dl">
		<xs:complexType>
			<xs:attribute name="name" type="xs:string" />
			<xs:attribute name="value" type="xs:string" />
		</xs:complexType>
	</xs:element>
	<xs:element name="message">
		<xs:complexType>
			<xs:attribute name="type" type="xs:string" />
		</xs:complexType>
	</xs:element>
	<xs:element name="manifest">
		<xs:complexType>
			<xs:all>
				<xs:element name="application" minOccurs="1" maxOccurs="1">
					<xs:complexType>
						<xs:all>
							<xs:element name="name" type="xs:string" minOccurs="1" maxOccurs="1" />
							<xs:element name="identifie" type="xs:string"  minOccurs="1" maxOccurs="1" />
							<xs:element name="version" type="xs:string"  minOccurs="1" maxOccurs="1" />
							<xs:element name="type" type="xs:string"  minOccurs="1" maxOccurs="1" />
							<xs:element name="ability" type="xs:string"  minOccurs="1" maxOccurs="1" />
							<xs:element name="description" type="xs:string"  minOccurs="1" maxOccurs="1" />
							<xs:element name="author" type="xs:string"  minOccurs="1" maxOccurs="1" />
							<xs:element name="url" type="xs:string"  minOccurs="1" maxOccurs="1" />
						</xs:all>
						<xs:attribute name="setting" type="xs:boolean" />
					</xs:complexType>
				</xs:element>
				<xs:element name="platform" minOccurs="0" maxOccurs="1">
					<xs:complexType>
						<xs:all>
							<xs:element name="subscribes" minOccurs="0" maxOccurs="1">
								<xs:complexType>
									<xs:sequence>
										<xs:element ref="message" minOccurs="0" maxOccurs="unbounded" />
									</xs:sequence>
								</xs:complexType>
							</xs:element>
							<xs:element name="handles" minOccurs="0" maxOccurs="1">
								<xs:complexType>
									<xs:sequence>
										<xs:element ref="message" minOccurs="0" maxOccurs="unbounded" />
									</xs:sequence>
								</xs:complexType>
							</xs:element>
							<xs:element name="rule" minOccurs="0" maxOccurs="1">
								<xs:complexType>
									<xs:attribute name="embed" type="xs:boolean" />
								</xs:complexType>
							</xs:element>
							<xs:element name="card" minOccurs="0" maxOccurs="1">
								<xs:complexType>
									<xs:attribute name="embed" type="xs:boolean" />
								</xs:complexType>
							</xs:element>
						</xs:all>
					</xs:complexType>
				</xs:element>
				<xs:element name="bindings" minOccurs="0" maxOccurs="1">
					<xs:complexType>
						<xs:all>
							<xs:element name="cover" minOccurs="0" maxOccurs="1">
								<xs:complexType>
									<xs:sequence>
										<xs:element ref="entry" minOccurs="0" maxOccurs="unbounded" />
									</xs:sequence>
									<xs:attribute name="call" type="xs:string" />
								</xs:complexType>
							</xs:element>
							<xs:element name="rule" minOccurs="0" maxOccurs="1">
								<xs:complexType>
									<xs:sequence>
										<xs:element ref="entry" minOccurs="0" maxOccurs="unbounded" />
									</xs:sequence>
									<xs:attribute name="call" type="xs:string" />
								</xs:complexType>
							</xs:element>
							<xs:element name="menu" minOccurs="0" maxOccurs="1">
								<xs:complexType>
									<xs:sequence>
										<xs:element ref="entry" minOccurs="0" maxOccurs="unbounded" />
									</xs:sequence>
									<xs:attribute name="call" type="xs:string" />
								</xs:complexType>
							</xs:element>
							<xs:element name="home" minOccurs="0" maxOccurs="1">
								<xs:complexType>
									<xs:sequence>
										<xs:element ref="entry" minOccurs="0" maxOccurs="unbounded" />
									</xs:sequence>
									<xs:attribute name="call" type="xs:string" />
								</xs:complexType>
							</xs:element>
							<xs:element name="profile" minOccurs="0" maxOccurs="1">
								<xs:complexType>
									<xs:sequence>
										<xs:element ref="entry" minOccurs="0" maxOccurs="unbounded" />
									</xs:sequence>
									<xs:attribute name="call" type="xs:string" />
								</xs:complexType>
							</xs:element>
							<xs:element name="shortcut" minOccurs="0" maxOccurs="1">
								<xs:complexType>
									<xs:sequence>
										<xs:element ref="entry" minOccurs="0" maxOccurs="unbounded" />
									</xs:sequence>
									<xs:attribute name="call" type="xs:string" />
								</xs:complexType>
							</xs:element>
							<xs:element name="function" minOccurs="0" maxOccurs="1">
								<xs:complexType>
									<xs:sequence>
										<xs:element ref="entry" minOccurs="0" maxOccurs="unbounded" />
									</xs:sequence>
									<xs:attribute name="call" type="xs:string" />
								</xs:complexType>
							</xs:element>
						</xs:all>
					</xs:complexType>
				</xs:element>
				<xs:element name="permissions" minOccurs="0" maxOccurs="1">
					<xs:complexType>
						<xs:sequence>
							<xs:element ref="entry" minOccurs="0" maxOccurs="unbounded" />
						</xs:sequence>
					</xs:complexType>
				</xs:element>
				<xs:element name="crons" minOccurs="0" maxOccurs="1">
					<xs:complexType>
						<xs:sequence>
							<xs:element name="item" minOccurs="0" maxOccurs="unbounded">
								<xs:complexType>
									<xs:sequence>
										<xs:element ref="dl" minOccurs="0" maxOccurs="unbounded" />
									</xs:sequence>
								</xs:complexType>
							</xs:element>
						</xs:sequence>
					</xs:complexType>
				</xs:element>
				<xs:element name="install" type="xs:string" minOccurs="0" maxOccurs="1" />
				<xs:element name="uninstall" type="xs:string" minOccurs="0" maxOccurs="1" />
				<xs:element name="upgrade" type="xs:string" minOccurs="0" maxOccurs="1" />
			</xs:all>
			<xs:attribute name="versionCode" type="xs:string" />
		</xs:complexType>
	</xs:element>
</xs:schema>
TPL;
	return trim($xsd);
}


function ext_template_manifest($tpl) {
	$filename = IA_ROOT . '/app/themes/' . $tpl . '/manifest.xml';
	if (!file_exists($filename)) {
		return array() ;
	}
	$manifest = ext_template_manifest_parse(file_get_contents($filename));
	if (empty($manifest['name']) || $manifest['name'] != $tpl) {
		return array();
	}
	return $manifest;
}


function ext_template_manifest_parse($xml) {
	$xml = str_replace(array('&'), array('&amp;'), $xml);
	$xml = @isimplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
	if (empty($xml)) {
		return array();
	}
	$manifest['name'] = strval($xml->identifie);
	$manifest['title'] = strval($xml->title);
	if (empty($manifest['title'])) {
		return array();
	}
	$manifest['type'] = !empty($xml->type) ? strval($xml->type) : 'other';
	$manifest['description'] = strval($xml->description);
	$manifest['author'] = strval($xml->author);
	$manifest['url'] = strval($xml->url);
	if (isset($xml->sections)) {
		$manifest['sections'] = strval($xml->sections);
	}
	if ($xml->settings->item) {
		foreach ($xml->settings->item as $msg) {
			$attrs = $msg->attributes();
			$manifest['settings'][] = array('key' => trim(strval($attrs['variable'])), 'value' => trim(strval($attrs['content'])), 'desc' => trim(strval($attrs['description'])));
		}
	}
	return $manifest;
}

function ext_pctemplate_manifest_parse($xml) {
	$xml = str_replace(array('&'), array('&amp;'), $xml);
	$xml = @isimplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
	if (empty($xml)) {
		return array();
	}
	$manifest['name'] = strval($xml->identifie);
	$manifest['title'] = strval($xml->title);
	if (empty($manifest['title'])) {
		return array();
	}
	$manifest['type'] = !empty($xml->type) ? strval($xml->type) : 'other';
	$manifest['description'] = strval($xml->description);
	$manifest['author'] = strval($xml->author);
	$manifest['url'] = strval($xml->url);
	if (isset($xml->sections)) {
		$manifest['sections'] = strval($xml->sections);
	}
	if ($xml->settings->item) {
		foreach ($xml->settings->item as $msg) {
			$attrs = $msg->attributes();
			$manifest['settings'][] = array('key' => trim(strval($attrs['variable'])), 'value' => trim(strval($attrs['content'])), 'desc' => trim(strval($attrs['description'])));
		}
	}
	return $manifest;
}
function ext_template_type() {
	static $types = array(
		'often' => array(
			'name' => 'often',
			'title' => '常用模板',
		),
		'rummery' => array(
			'name' => 'rummery',
			'title' => '酒店',
		),
		'car' => array(
			'name' => 'car',
			'title' => '汽车',
		),
		'tourism' => array(
			'name' => 'tourism',
			'title' => '旅游',
		),
		'drink' => array(
			'name' => 'drink',
			'title' => '餐饮',
		),
		'realty' => array(
			'name' => 'realty',
			'title' => '房地产',
		),
		'medical' => array(
			'name' => 'medical',
			'title' => '医疗保健'
		),
		'education' => array(
			'name' => 'education',
			'title' => '教育'
		),
		'cosmetology' => array(
			'name' => 'cosmetology',
			'title' => '健身美容'
		),
		'shoot' => array(
			'name' => 'shoot',
			'title' => '婚纱摄影'
		),
		'other' => array(
			'name' => 'other',
			'title' => '其它'
		)
	);
	return $types;
}

function ext_pctemplate_type() {
	static $types = array(
		'often' => array(
			'name' => 'often',
			'title' => '常用PC模板',
		),
		'site' => array(
			'name' => 'site',
			'title' => '微站PC模板',
		),
		'shop' => array(
			'name' => 'shop',
			'title' => '商城',
		),
		'drink' => array(
			'name' => 'drink',
			'title' => '餐饮',
		),
		'education' => array(
			'name' => 'education',
			'title' => '教育',
		),
		'realty' => array(
			'name' => 'realty',
			'title' => '房地产',
		),
		'medical' => array(
			'name' => 'medical',
			'title' => '医疗保健'
		),
		'cosmetology' => array(
			'name' => 'cosmetology',
			'title' => '健身美容'
		),
		'shoot' => array(
			'name' => 'shoot',
			'title' => '婚纱摄影'
		),
		'other' => array(
			'name' => 'other',
			'title' => '其它'
		)
	);
	return $types;
}

function ext_module_script_clean($modulename, $manifest) {
	$moduleDir = IA_ROOT . '/addons/' . $modulename . '/';
	$manifest['install'] = trim($manifest['install']);
	$manifest['uninstall'] = trim($manifest['uninstall']);
	$manifest['upgrade'] = trim($manifest['upgrade']);
	if (strexists($manifest['install'], '.php')) {
		if (file_exists($moduleDir . $manifest['install'])) {
			unlink($moduleDir . $manifest['install']);
		}
	}
	if (strexists($manifest['uninstall'], '.php')) {
		if (file_exists($moduleDir . $manifest['uninstall'])) {
			unlink($moduleDir . $manifest['uninstall']);
		}
	}
	if (strexists($manifest['upgrade'], '.php')) {
		if (file_exists($moduleDir . $manifest['upgrade'])) {
			unlink($moduleDir . $manifest['upgrade']);
		}
	}
}


function ext_module_msg_types() {
	$mtypes = array();
	$mtypes['text'] = '文本消息(重要)';
	$mtypes['image'] = '图片消息';
	$mtypes['voice'] = '语音消息';
	$mtypes['video'] = '视频消息';
	$mtypes['shortvideo'] = '小视频消息';
	$mtypes['location'] = '位置消息';
	$mtypes['link'] = '链接消息';
	$mtypes['subscribe'] = '粉丝开始关注';
	$mtypes['unsubscribe'] = '粉丝取消关注';
	$mtypes['qr'] = '扫描二维码';
	$mtypes['trace'] = '追踪地理位置';
	$mtypes['click'] = '点击菜单(模拟关键字)';
	$mtypes['view'] = '点击菜单(链接)';
	$mtypes['merchant_order'] = '微小店消息';
	return $mtypes;
}

function ext_check_module_subscribe($modulename) {
	global $_W;
	if (empty($modulename)) {
		return true;
	}
	if (!is_array($_W['setting']['module_receive_ban'])) {
		$_W['setting']['module_receive_ban'] = array();
	}
	
	$response = ihttp_request($_W['siteroot'] . url('extension/subscribe/check', array('modulename' => $modulename)));
	if (strexists($response['content'], 'success')) {
		unset($_W['setting']['module_receive_ban'][$modulename]);
		$module_subscribe_success = true;
	} else {
		$_W['setting']['module_receive_ban'][$modulename] = $modulename;
		$module_subscribe_success = false;
	}
	setting_save($_W['setting']['module_receive_ban'], 'module_receive_ban');
	return $module_subscribe_success;
}
