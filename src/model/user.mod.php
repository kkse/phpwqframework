<?php
/**
 * [ECOCOM System] Copyright (c) 2014 DTROAD.COM
 * ECOCOM is NOT a free software, it under the license terms, visited http://www.dtroad.com/ for more details.
 */
use Com\Internal\DbCache;

defined('IN_IA') or exit('Access Denied');


function user_check($user) {
	if (empty($user) || !is_array($user)) {
		return false;
	}
	$where = ' WHERE 1 ';
	$params = array();
	if (!empty($user['uid'])) {
		$where .= ' AND `uid`=:uid';
		$params[':uid'] = intval($user['uid']);
	}
	if (!empty($user['username'])) {
		$where .= ' AND `username`=:username';
		$params[':username'] = $user['username'];
	}
	if (!empty($user['status'])) {
		$where .= " AND `status`=:status";
		$params[':status'] = intval($user['status']);
	}
	if (empty($params)) {
		return false;
	}
	$sql = 'SELECT `password`,`salt` FROM ' . tablename('users') . "$where LIMIT 1";
	$record = pdo_fetch($sql, $params);
	if (empty($record) || empty($record['password']) || empty($record['salt'])) {
		return false;
	}
	if (!empty($user['password'])) {
		$password = user_hash($user['password'], $record['salt']);
		return $password == $record['password'];
	}
	return true;
}

function user_single($user_or_uid)
{
	$user = $user_or_uid;
	if (empty($user)) {
		return false;
	}
	if (is_numeric($user)) {
		$user = array('uid' => $user);
	}
	if (!is_array($user)) {
		return false;
	}
    $where = ' WHERE 1 ';
    $params = array();
    if (!empty($user['uid'])) {
        $where .= ' AND `uid`=:uid';
        $params[':uid'] = intval($user['uid']);
    }
    if (!empty($user['username'])) {
        $where .= ' AND `username`=:username';
        $params[':username'] = $user['username'];
    }
    if (!empty($user['email'])) {
        $where .= ' AND `email`=:email';
        $params[':email'] = $user['email'];
    }
    if (!empty($user['status'])) {
        $where .= " AND `status`=:status";
        $params[':status'] = intval($user['status']);
    }
    if (empty($params)) {
        return false;
    }

//	$record = DbCache::data_get(tablename('users', false), $params);
    $sql = 'SELECT * FROM ' . tablename('users') . " $where LIMIT 1";
    $record = pdo_fetch($sql, $params);
	if (empty($record)) {
		return false;
	}
	if (!empty($user['password'])) {
		$password = user_hash($user['password'], $record['salt']);
		if ($password != $record['password']) {
			return false;
		}
	}
//	if(!empty($record['agentid'])){
//		$url=$_SERVER['SERVER_NAME'];
//		$record1 = pdo_get('agent_copyright', array('uid'=>$record['agentid']));
//		if($record1['yuming']!=$url){
//			message('登录失败，账户不存在或者密码错误！');
//		}
//	}
    $record['name'] = $record['username'];
    $record['clerk_id'] = $record['uid'];
    $record['store_id'] = 0;
    $record['clerk_type'] = '2';
	return $record;
}

function user_update($user) {
	if (empty($user['uid']) || !is_array($user)) {
		return false;
	}
	$record = array();
	if (!empty($user['username'])) {
		$record['username'] = $user['username'];
	}
	if (!empty($user['password'])) {
		$record['password'] = user_hash($user['password'], $user['salt']);
	}
	if (!empty($user['lastvisit'])) {
		$record['lastvisit'] = (strlen($user['lastvisit']) == 10) ? $user['lastvisit'] : strtotime($user['lastvisit']);
	}
	if (!empty($user['lastip'])) {
		$record['lastip'] = $user['lastip'];
	}
	if (isset($user['joinip'])) {
		$record['joinip'] = $user['joinip'];
	}
	if (isset($user['remark'])) {
		$record['remark'] = $user['remark'];
	}
	if (isset($user['remark'])) {
		$record['remark'] = $user['remark'];
	}
	
	
	if (isset($user['status'])) {
		$status = intval($user['status']);
		if (!in_array($status, array(1, 2))) {
			$status = 2;
		}
		$record['status'] = $status;
	}
	if (isset($user['groupid'])) {
		$record['groupid'] = $user['groupid'];
	}
	if (isset($user['starttime'])) {
		$record['starttime'] = $user['starttime'];
	}
	if (isset($user['endtime'])) {
		$record['endtime'] = $user['endtime'];
	}
	
	//新增
	if (isset($user['idtype'])) {
		$record['idtype'] = $user['idtype'];
	}
	if (isset($user['idstr'])) {
		$record['idstr'] = $user['idstr'];
	}
	if (isset($user['orgcode'])) {
		$record['orgcode'] = $user['orgcode'];
	}
	if (isset($user['netcode'])) {
		$record['netcode'] = $user['netcode'];
	}
	if (isset($user['person_no'])) {
		$record['person_no'] = $user['person_no'];
	}
	if (isset($user['depcode'])) {
		$record['depcode'] = $user['depcode'];
	}
	if (isset($user['role'])) {
		$record['role'] = $user['role'];
	}
	if (isset($user['role_id'])) {
		$record['role_id'] = $user['role_id'];
	}
	
	if (empty($record)) {
		return false;
	}

    return DbCache::data_update(tablename('users', false), $record, ['uid' => intval($user['uid'])]);
	//return pdo_update('users', $record, array('uid' => intval($user['uid'])));
}


function user_hash($passwordinput, $salt) {
	$passwordinput = "{$passwordinput}-{$salt}-".config('AUTHKEY');
	return sha1($passwordinput);
}

function user_register($user) {
    if (empty($user) || !is_array($user)) {
        return 0;
    }
    if (isset($user['uid'])) {
        unset($user['uid']);
    }
    $user['salt'] = random(8);
    $user['password'] = user_hash($user['password'], $user['salt']);
    $user['joinip'] = CLIENT_IP;
    $user['joindate'] = TIMESTAMP;
    $user['lastip'] = CLIENT_IP;
    $user['lastvisit'] = TIMESTAMP;
    if (empty($user['status'])) {
        $user['status'] = 2;
    }
    $now = time();
    if(empty($user['endtime'])){
        $user['endtime'] = $now + 7 * 24 * 3600;
    }
    $result = pdo_insert('users', $user);
    if (!empty($result)) {
        $user['uid'] = pdo_insertid();
    }
    return intval($user['uid']);
}
