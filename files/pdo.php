<?php

function xz_sql($sql, $params = array())
{
    if (!$params) {
        return $sql;
    }
	$sql = str_replace("%", "%%", $sql);
	$i = 1;
	foreach ($params as $key => $val) {
		$key{0} == ':' or $key = ':'.$key;
		$sql = str_replace($key, "'%{$i}\$s'", $sql);
		$i ++;
	}

	return $sql;
}

function xz_params($params = array())
{
    if (!$params) {
        return [];
    }
    $xz_params = [];

    foreach ($params as $key => $val) {
        $key{0} == ':' and $key = substr($key, 1);

        $xz_params[$key] = $val;
    }
    return $xz_params;
}

function xz_error(\Exception $e, $sql, $params = array())
{
	$traces = $e->getTrace();
	$error = $e->getMessage();
	$ts = '';
	foreach($traces as $trace) {
		$trace['file'] = str_replace('\\', '/', $trace['file']);
		$trace['file'] = str_replace(IA_ROOT, '', $trace['file']);
		$ts .= "file: {$trace['file']}; line: {$trace['line']}; <br />";
	}
	$params = var_export($params, true);
	if (function_exists('message')) {
        message("SQL: <br/>{$sql}<hr/>Params: <br/>{$params}<hr/>SQL Error: <br/>{$error}<hr/>Traces: <br/>{$ts}");
	} else {
	    E("SQL: <br/>{$sql}<hr/>Params: <br/>{$params}<hr/>SQL Error: <br/>{$error}<hr/>Traces: <br/>{$ts}");
    }
}

function pdo_query($sql, $params = array())
{
    //$xz_sql = xz_sql($sql, $params);
    //$xz_params = $params?array_values($params):false;

    $xz_params = xz_params($params);

	try {
		return M()->execute($sql, $xz_params);
	} catch (\Exception $e) {
		xz_error($e, $sql, $xz_params);exit;
	}
}


function pdo_fetchcolumn($sql, $params = array(), $column = 0) {

    //$xz_sql = xz_sql($sql, $params);
    //$xz_params = $params?array_values($params):false;

    $xz_params = xz_params($params);
	try {
	    //var_dump($xz_sql, $xz_params);
		$rows = M()->query($sql, $xz_params);
		if ($rows) {
			$row = reset($rows);
			if ($column == 0) {
				return reset($row);
			} else {
				$row = array_values($row);
				return isset($row[$column])?$row[$column]:null;
			}
			
		} else {
			return null;
		}
	} catch (\Exception $e) {
		xz_error($e, $sql, $xz_params);
		exit;
	}
}

function pdo_fetch($sql, $params = array()) {
    //$xz_sql = xz_sql($sql, $params);
    //$xz_params = $params?array_values($params):false;
    $xz_params = xz_params($params);
	try {
		$rows = M()->query($sql, $xz_params);
		if ($rows) {
			return reset($rows);
		} else {
			return false;
		}
	} catch (\Exception $e) {
		xz_error($e, $sql, $xz_params);
		exit;
	}
}

function toKeyArr($rows, $keyfield)
{
	if (!$rows || !is_array($rows) || !$keyfield) {
		return $rows;
	}

	$row = reset($rows);
	if (!is_array($row) || !isset($row[$keyfield])) {
		return $rows;
	}
	$data = [];
	foreach ($rows as $row) {
		$data[$row[$keyfield]] = $row;
	}
	return $data;

}

/**
 * @param null $set
 * @return bool
 *
 */
function pdo_debug($set = null) {
    static $debug = false;
    if (is_null($set)) {
        return $debug;
    }
    $debug = (bool)$set;
    return true;
}

function pdo_fetchall($sql, $params = array(), $keyfield = '') {

    $xz_params = xz_params($params);
	try {
		$rows = M()->query($sql, $xz_params);
		return toKeyArr($rows, $keyfield);
	} catch (\Exception $e) {
		xz_error($e, $sql, $xz_params);exit;
	}
}


function pdo_get($tablename, $condition = array(), $fields = array(), $order = []) {
	try {
		return M(tablename($tablename, false), null)
            ->field($fields)
            ->where($condition)
            ->order($order)
            ->find();
	} catch (\Exception $e) {
		xz_error($e, 'SELECT '.$tablename, $condition);
		exit;
	}
}

//按照分页参数进行查询
function pdo_getlist($tablename, $pindex, $psize = 20, $condition = [], $order = [], $fields = []) {
    try {
        $rows = M(tablename($tablename, false), null)
            ->field($fields)
            ->order($order)
            ->page($pindex, $psize)
            ->where($condition)
            ->select();
        return $rows;
    } catch (\Exception $e) {
        xz_error($e, 'SELECT '.$tablename, $condition);
        exit;
    }
}

function pdo_getall($tablename, $condition = array(), $fields = array(), $order = [], $keyfield = '') {
	try {
		$rows = M(tablename($tablename, false), null)
            ->field($fields)
            ->where($condition)
            ->order($order)
            ->select();
		return toKeyArr($rows, $keyfield);
	} catch (\Exception $e) {
		xz_error($e, 'SELECT '.$tablename, $condition);
		exit;
	}
}


function pdo_getpairs($tablename, $condition, $field_key, $field_val, $order = []) {
    try {
        return M(tablename($tablename, false), null)
            ->where($condition)
            ->order($order)
            ->column($field_val, $field_key);
    } catch (\Exception $e) {
        xz_error($e, 'SELECT '.$tablename, $condition);
        exit;
    }
}

function pdo_getpairslist($tablename, $condition, $field_key, $field_val, $pindex, $psize = 20,$order = []) {
    try {
        return M(tablename($tablename, false), null)
            ->where($condition)
            ->page($pindex, $psize)
            ->order($order)
            ->column($field_val, $field_key);
    } catch (\Exception $e) {
        xz_error($e, 'SELECT '.$tablename, $condition);
        exit;
    }
}

/*
function pdo_getslice($tablename, $condition = array(), $limit = array(), &$total = null, $fields = array(), $keyfield = '') {
	return pdo()->getslice($tablename, $condition, $limit, $total, $fields, $keyfield);
}*/

function pdo_getcolumn($tablename, $condition, $field, $order = '') {
	try {
		return M(tablename($tablename, false), null)->where($condition)->order($order)->value($field);
	} catch (\Exception $e) {
		xz_error($e, 'SELECT '.$tablename, $condition);
		exit;
	}
}

function pdo_getcolumns($tablename, $condition, $field, $key = '', $order = '') {
    try {
        return M(tablename($tablename, false), null)
            ->where($condition)
            ->order($order)
            ->column($field, $key);
    } catch (\Exception $e) {
        xz_error($e, 'SELECT '.$tablename, $condition);
        exit;
    }
}

function pdo_getcount($tablename, $condition) {
    return pdo_getcolumn($tablename, $condition, 'COUNT(1)');
}

function pdo_update($table, $data = array(), $params = array()) {
	try {
		return M(tablename($table, false), null)->where($params)->update($data);
	} catch (\Exception $e) {
		xz_error($e, 'UPDATE '.$table, $data);
		exit;
	}
}


function pdo_insert($table, $data = array(), $replace = FALSE) {
	try {
		foreach ($data as $k => $v) {
			if (is_null($v)) {
				$data[$k] = '';
			}
		}
		$m = M(tablename($table, false), null);
        $ret = $m->insert($data, $replace);
		if ($ret) {
            $insID = $m->getLastInsID();
            return $insID?:$ret;
        } else {
		    return false;
        }
	} catch (\Exception $e) {
		xz_error($e, 'INSERT '.$table, $data);
		exit;
	}
}


function pdo_delete($table, $params = array()) {
    $params or $params = '1';
	try {
		return M(tablename($table, false), null)->where($params)->delete();
	} catch (\Exception $e) {
		xz_error($e, 'DELETE '.$table, $params);
		exit;
	}
}

function pdo_getpk($table) {
    return M()->getPk($table);
}

function pdo_insertid() {
	return M()->getLastInsID();
}


function pdo_begin() {
	M()->startTrans();
}


function pdo_commit() {
	M()->commit();
}


function pdo_rollback() {
	M()->rollBack();
}

/*
function pdo_debug($output = true, $append = array()) {
	return pdo()->debug($output, $append);
}*/

function pdo_run($sql) {
	if(!isset($sql) || empty($sql)) return;

	$stuff = 'ims_';
	$tablepre = config('DB_TABLEPRE');

	$sql = str_replace("\r", "\n", str_replace(' ' . $stuff, ' ' . $tablepre, $sql));
	$sql = str_replace("\r", "\n", str_replace(' `' . $stuff, ' `' . $tablepre, $sql));
	$ret = array();
	$num = 0;
	$sql = preg_replace("/\;[ \f\t\v]+/", ';', $sql);
	foreach(explode(";\n", trim($sql)) as $query) {
		$ret[$num] = '';
		$queries = explode("\n", trim($query));
		foreach($queries as $_query) {
			$ret[$num] .= (isset($_query[0]) && $_query[0] == '#') || (isset($_query[1]) && isset($_query[1]) && $_query[0].$_query[1] == '--') ? '' : $_query;
		}
		$num++;
	}
	unset($sql);
	foreach($ret as $query) {
		$query = trim($query);
		if($query) {
			M()->execute($query);
		}
	}
}


function pdo_fieldexists($tablename, $fieldname = '') {
	
	$isexists = M()->query("DESCRIBE " . tablename($tablename, false) . " `{$fieldname}`");
	return !empty($isexists);
}


function pdo_indexexists($tablename, $indexname = '') {
	if (!empty($indexname)) {
		$indexs = M()->query("SHOW INDEX FROM " . tablename($tablename, false));
		if (!empty($indexs) && is_array($indexs)) {
			foreach ($indexs as $row) {
				if ($row['Key_name'] == $indexname) {
					return true;
				}
			}
		}
	}
	return false;
}


function pdo_fetchallfields($tablename){
	$rows = M()->query("DESCRIBE ".tablename($tablename, false));
	return array_column($rows, 'Field');
}


function pdo_tableexists($tablename){
	if(!empty($tablename)) {
		$data = M()->query("SHOW TABLES LIKE '".tablename($tablename, false)."'");
		$data = reset($data);
		if(!empty($data)) {
			$data = array_values($data);
			if(in_array(tablename($tablename, false), $data)) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	} else {
		return false;
	}
}
