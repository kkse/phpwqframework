<?php
/**
 * [ECOCOM System] Copyright (c) 2015 DTROAD.COM
 * DTROAD is NOT a free software, it under the license terms, visited http://www.dtroad.com/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

function article_categorys($type = 'news') {
	$categorys = pdo_fetchall('SELECT * FROM ' . tablename('article_category') . ' WHERE type = :type ORDER BY displayorder DESC', array(':type' => $type), 'id');
	return $categorys;
}
function article_catecase($type = 'case') {
	$catecases = pdo_fetchall('SELECT * FROM ' . tablename('article_catecase') . ' WHERE type = :type ORDER BY displayorder DESC', array(':type' => $type), 'id');
	return $catecases;
}


function article_notice_info($id) {
	$id = intval($id);
	$news = pdo_fetch('SELECT * FROM ' . tablename('article_notice') . ' WHERE id = :id', array(':id' => $id));
	$news['info'] = cutstr(strip_tags($news['content']), 88);
	if(empty($news)) {
		return error(-1, '公告不存在或已经删除');
	}
	return $news;
}


function article_notice_home($limit = 10) {
	$limit = intval($limit);
	$notice = pdo_fetchall('SELECT * FROM ' . tablename('article_notice') . ' WHERE is_display = 1 AND is_show_home = 1 ORDER BY displayorder DESC,id DESC LIMIT ' . $limit, array(), 'id');
	return $notice;
}

function article_notice_all($filter = array(), $pindex = 1, $psize = 10) {
	$condition = ' WHERE is_display = 1';
	$params = array();
	if(!empty($filter['title'])) {
		$condition .= ' AND titie LIKE :title';
		$params[':title'] = "%{$filter['title']}%";
	}
	if($filter['cateid'] > 0) {
		$condition .= ' AND cateid = :cateid';
		$params[':cateid'] = $filter['cateid'];
	}
	$limit = ' LIMIT ' . ($pindex - 1) * $psize . ',' . $psize;
	$total = pdo_fetchcolumn('SELECT COUNT(1) FROM ' . tablename('article_notice') . $condition, $params);
	$notice = pdo_fetchall('SELECT * FROM ' . tablename('article_notice') . $condition . ' ORDER BY displayorder DESC ' . $limit, $params, 'id');
	return array('total' => $total, 'notice' => $notice);
}

function cutstr_html($string, $sublen){
  $string = strip_tags($string);
  $string = preg_replace ('/\n/is', '', $string);
  $string = preg_replace ('/ |　/is', '', $string);
  $string = preg_replace ('/&nbsp;/is', '', $string);
  preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $string, $t_string);
  if(count($t_string[0]) - 0 > $sublen) $string = join('', array_slice($t_string[0], 0, $sublen))."…";   
  else $string = join('', array_slice($t_string[0], 0, $sublen));
  return $string;
}
function cutstr_num($string, $sublen){
$string = strip_tags($string);
  preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $string, $t_string);
  if(count($t_string[0]) - 0 > $sublen) $string = join('', array_slice($t_string[0], 0, $sublen))."…";   
  else $string = join('', array_slice($t_string[0], 0, $sublen));
  return $string;
}



