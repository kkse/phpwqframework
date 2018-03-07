<?php
/**
 * [ECOCOM System] Copyright (c) 2015 DTROAD.COM
 * DTROAD is NOT a free software, it under the license terms, visited http://www.dtroad.com/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

function frame_lists(){
	global $_W;

	$data = core_menu_fetchall("pid = 0", '*', 'is_system ASC, displayorder ASC, id ASC');
	//$data = pdo_fetchall('SELECT * FROM ' . tablename('core_menu') . ' WHERE pid = 0 '.$add_where.' ORDER BY is_system ASC, displayorder ASC, id ASC');
	if(!empty($data)) {
		foreach($data as &$da) {
			$childs = core_menu_fetchall("pid = {$da['id']}", '*', 'is_system ASC, displayorder ASC, id ASC');

			//$childs = pdo_fetchall('SELECT * FROM ' . tablename('core_menu') . ' WHERE pid = :pid '.$add_where.' ORDER BY is_system ASC, displayorder ASC, id ASC', array(':pid' => $da['id']));
			if(!empty($childs)) {
				foreach($childs as &$child) {
					$grandchilds = core_menu_fetchall("pid = {$child['id']}", '*', 'is_system ASC, displayorder ASC, id ASC');
					//$grandchilds = pdo_fetchall('SELECT * FROM ' . tablename('core_menu') . ' WHERE pid = :pid '.$add_where.' ORDER BY is_system ASC, displayorder ASC, id ASC', array(':pid' => $child['id']));
					if(!empty($grandchilds)) {
						foreach($grandchilds as &$grandchild) {
							$greatsons = core_menu_fetchall("pid = {$grandchild['id']}", '*', 'is_system ASC, displayorder ASC, id ASC');
							//$greatsons = pdo_fetchall('SELECT * FROM ' . tablename('core_menu') . ' WHERE pid = :pid '.$add_where.' ORDER BY is_system ASC, displayorder ASC, id ASC', array(':pid' => $grandchild['id']));
							$grandchild['greatsons'] = $greatsons;
						}
					}
					$child['grandchild'] = $grandchilds;
				}
				$da['child'] = $childs;
			}
		}
	}
	return $data;
}


function custom_frame_lists(){
	global $_W;

	$data = user_menu_fetchall("pid = 0", '*', 'is_system ASC, displayorder ASC, id ASC');
	//$data = pdo_fetchall('SELECT * FROM ' . tablename('core_menu') . ' WHERE pid = 0 '.$add_where.' ORDER BY is_system ASC, displayorder ASC, id ASC');
	if(!empty($data)) {
		foreach($data as &$da) {
			$childs = user_menu_fetchall("pid = {$da['id']}", '*', 'is_system ASC, displayorder ASC, id ASC');

			//$childs = pdo_fetchall('SELECT * FROM ' . tablename('core_menu') . ' WHERE pid = :pid '.$add_where.' ORDER BY is_system ASC, displayorder ASC, id ASC', array(':pid' => $da['id']));
			if(!empty($childs)) {
				foreach($childs as &$child) {
					$grandchilds = user_menu_fetchall("pid = {$child['id']}", '*', 'is_system ASC, displayorder ASC, id ASC');
					//$grandchilds = pdo_fetchall('SELECT * FROM ' . tablename('core_menu') . ' WHERE pid = :pid '.$add_where.' ORDER BY is_system ASC, displayorder ASC, id ASC', array(':pid' => $child['id']));
					if(!empty($grandchilds)) {
						foreach($grandchilds as &$grandchild) {
							$greatsons = user_menu_fetchall("pid = {$grandchild['id']}", '*', 'is_system ASC, displayorder ASC, id ASC');
							//$greatsons = pdo_fetchall('SELECT * FROM ' . tablename('core_menu') . ' WHERE pid = :pid '.$add_where.' ORDER BY is_system ASC, displayorder ASC, id ASC', array(':pid' => $grandchild['id']));
							$grandchild['greatsons'] = $greatsons;
						}
					}
					$child['grandchild'] = $grandchilds;
				}
				$da['child'] = $childs;
			}
		}
	}
	return $data;
}

//优化版
function frame_lists_fast($tablename = '',$where = '')
{	

    $tablename AND $tablename = tablename($tablename);
    //$tablename = tablename('core_menu');
	$where AND $where = ' WHERE '.$where;

    $rows = pdo_fetchall('SELECT id,eid,pid,title,name,url,displayorder,is_display,nav_content,menu_type,permission_name FROM ' . $tablename . $where.' ORDER BY displayorder DESC, id ASC');

    $data = array();
    if (!empty($rows)) {
        foreach ($rows as $key => $row) {
            if ($row['pid'] == 0) {
                $data[] = $row;
                unset($rows[$key]);
            }
        }

        foreach ($data as $key => $item) {
            $childs = array();
            foreach ($rows as $rkey => $row) {
                if ($row['pid'] == $item['id']) {
                    $childs[] = $row;
                    unset($rows[$rkey]);
                }
            }

            foreach ($childs as $ckey => $child) {
                $grandchilds = array();
                foreach ($rows as $rkey => $row) {
                    if ($row['pid'] == $child['id']) {
                        $grandchilds[] = $row;
                        unset($rows[$rkey]);
                    }
                }

                /*foreach ($grandchilds as $gkey => $grandchild) {
                    $greatsons = array();
                    foreach ($rows as $rkey => $row) {
                        if ($row['pid'] == $child['id']) {
                            $greatsons[] = $row;
                            unset($rows[$rkey]);
                        }
                    }
                    $grandchilds[$gkey]['greatsons'] = $greatsons;
                }*/
                $childs[$ckey]['grandchild'] = $grandchilds;
            }
            $data[$key]['child'] = $childs;
        }
    }
    return $data;
}

function frame_lists_cate($tablename = 'dt_businessman_cate',$where = '')
{	
	$condition = " WHERE 1 ";
	if($where){
		$condition .= $condition.$where;
		
	}
    $rows = pdo_fetchall('SELECT * FROM ' . tablename($tablename) . $condition .' ORDER BY sort DESC, id DESC');
    
    $data = array();
    if (!empty($rows)) {
        foreach ($rows as $key => $row) {
            if ($row['pid'] == 0) {
                $data[] = $row;
                unset($rows[$key]);
            }
        }

        foreach ($data as $key => $item) {
            $childs = array();
            foreach ($rows as $rkey => $row) {
                if ($row['pid'] == $item['id']) {
                    $childs[] = $row;
                    unset($rows[$rkey]);
                }
            }

            foreach ($childs as $ckey => $child) {
                $grandchilds = array();
                foreach ($rows as $rkey => $row) {
                    if ($row['pid'] == $child['id']) {
                        $grandchilds[] = $row;
                        unset($rows[$rkey]);
                    }
                }

                foreach ($grandchilds as $gkey => $grandchild) {
                    $greatsons = array();
                    foreach ($rows as $rkey => $row) {
                        if ($row['pid'] == $child['id']) {
                            $greatsons[] = $row;
                            unset($rows[$rkey]);
                        }
                    }
                    $grandchilds[$gkey]['greatsons'] = $greatsons;
                }
                $childs[$ckey]['grandchild'] = $grandchilds;
            }
            $data[$key]['child'] = $childs;
        }
    }
    return $data;
}
//包含action的菜单列表
function frame_action_menus_fast($mune_mode = '', $permission = array())
{
    $rows = pdo_fetchall('SELECT id,pid,title,name,permission_name FROM ' . tablename('core_menu') . ' ORDER BY is_system ASC, displayorder DESC, id ASC');

    $actions = pdo_fetchall("SELECT action_cn,menu_permission,action_val FROM dt_sys_action WHERE is_display = 1 AND FIND_IN_SET('{$mune_mode}',permission_dispaly) ORDER BY id ASC");
    
    $data = array();
    if (!empty($rows)) {
        foreach ($rows as $key => $row) {
            if ($row['pid'] == 0) {
                $data[] = $row;
                unset($rows[$key]);
            }
        }


        foreach ($data as $key => $item) {
            $childs = getAllChilds($rows, $item['id']);
            $menus = [];
            foreach ($childs as $child) {
                if ($child['permission_name'] && (!$permission || in_array($child['permission_name'], $permission))) {
                    $child['actions'] = getAllActions($actions, $child['permission_name']);
                    $menus[] = $child;
                }
            }
            
            if ($menus) {
                $data[$key]['childs'] = $menus;
            } else {
                unset($data[$key]);
            }
        }
    }
    return array_values($data);
}

//包含action的菜单列表,只列第三级
function frame_action_menus_fast2($mune_mode = '', $permission = array(),$idstr='')
{
    global $_W;
	$where .= ' WHERE idtype = "'. $mune_mode .'" AND is_display = 1 ';
    $_W['user']['idstr'] = isset($idstr)?$idstr:$_W['user']['idstr'];
	if($mune_mode == 'ORG'){
        $where .= ' AND idstr = "'. $_W['user']['idstr'] .'"';
	}
	
    $rows = pdo_fetchall('SELECT id,pid,title,name,permission_name FROM ' . tablename('core_menu') . $where . ' ORDER BY is_system ASC, displayorder DESC, id ASC');
    
    $actions = pdo_fetchall("SELECT action_cn,menu_permission,action_val FROM dt_sys_action WHERE is_display = 1 AND FIND_IN_SET('{$mune_mode}',permission_dispaly) ORDER BY id ASC");

    $data = array();
    if (!empty($rows)) {
        foreach ($rows as $key => $row) {
            if ($row['pid'] == 0) {
                $data[] = $row;
                unset($rows[$key]);
            }
        }

        foreach ($data as $key => $item) {
            $childs = $tmp_childs = array();
            foreach ($rows as $rkey => $row) {
                if ($row['pid'] == $item['id']) {
                    $tmp_childs[] = $row;
                    unset($rows[$rkey]);
                }
            }

            foreach ($tmp_childs as $ckey => $child) {
                foreach ($rows as $rkey => $row) {
                    if ($row['pid'] == $child['id']) {
                        if ($row['permission_name'] && $row['permission_name'] != 'dt_' && (!$permission || in_array($row['permission_name'], $permission))) {
                            $row['actions'] = getAllActions($actions, $row['permission_name']);
                            $childs[] = $row;
                        }
                        unset($rows[$rkey]);
                    }
                }
            }

            if ($childs) {
                $data[$key]['childs'] = $childs;
            } else {
                unset($data[$key]);
            }
        }
    }//print_r($data);exit;
    return array_values($data);
}

//包含action的菜单列表,只列第三级
function frame_action_menus_fast3($mune_mode = '', $permission = array())
{	
	global $_W;
    $mune_mode or $mune_mode = $idtype = $_W['user']['idtype'];

    //权限添加

    $where =
	
    $data = pdo_fetchall('SELECT mid,name,title,permissions FROM ' . tablename('modules') . $where .' ORDER BY mid ASC');
	
	$rows = pdo_fetchall('SELECT eid,title,module,do FROM ' . tablename('modules_bindings') . $where .' ORDER BY displayorder ASC, eid ASC');

    $actions = pdo_fetchall("SELECT action_cn,menu_permission,action_val FROM dt_sys_action WHERE is_display = 1 AND FIND_IN_SET('{$mune_mode}',permission_dispaly) ORDER BY id ASC");

    if (!empty($rows)) {
       
        foreach ($data as $key => $item) {
            $childs = $tmp_childs = array();
            foreach ($rows as $rkey => $row) {
				if($row){
					if ($row['module'] == $item['name']) {
						if ($row['do'] && (!$permission || in_array($row['do'], $permission))) {
							$row['actions'] = getAllActions($actions, $row['do']);
							$childs[] = $row;
						}
					}
				}
            }
			$data[$key]['childs'] = $childs;
            /* foreach ($tmp_childs as $ckey => $child) {
                foreach ($rows as $rkey => $row) {
                    if ($row['pid'] == $child['id']) {
                        if ($row['permission_name'] && (!$permission || in_array($row['permission_name'], $permission))) {
                            $row['actions'] = getAllActions($actions, $row['permission_name']);
                            $childs[] = $row;
                        }
                        unset($rows[$rkey]);
                    }
                }
            } */

            /* if ($childs) {
                $data[$key]['childs'] = $childs;
            } else {
                unset($data[$key]);
            } */
        }
    }
	//print_r($data);exit;
	 //return array_values($data);
    return $data;
	
}


function getAllChilds(&$rows, $pid)
{
    $childs = [];
    foreach ($rows as $rkey => $row) {
        if ($row['pid'] == $pid) {
            $childs[] = $row;
            unset($rows[$rkey]);
        }
    }
    if ($childs && $rows) {
        $subchilds = [];
        foreach ($childs as $child) {
            $subchilds = array_merge($subchilds, getAllChilds($rows, $child['id']));
        }

        $childs = array_merge($childs, $subchilds);
    }
    
    return $childs;
}
function getAllActions($rows, $permission)
{
    $actions = [];
    foreach ($rows as $rkey => $row) {
        if ($row['menu_permission'] == $permission) {
            $actions[] = $row;
            unset($rows[$rkey]);
        }
    }
    $actions and array_unshift($actions, array(
        'action_cn'=>'查询列表',
        'menu_permission'=>$permission,
        'action_val'=>'display',));
    return $actions;
}

function frame_to_menus($menus, $permission = array())
{
    foreach ($menus as $key => $li) {
        $menus[$key]['childs'] = array();

        if (!empty($li['child'])) {
            foreach ($li['child'] as $da) {
                if (!empty($da['grandchild'])) {
                    foreach ($da['grandchild'] as $ca) {
                        if (!$permission || in_array($ca['permission_name'], $permission)) {
                            $menus[$key]['childs'][] = $ca;
                        }
                    }
                }
            }
            unset($menus[$key]['child']);
        }

        if (!$menus[$key]['childs']) {
            unset($menus[$key]);
        }
    }
    return array_values($menus);
}

function frame_action_menus_fast3_diy($mune_mode = '', $permission = array(),$id='')
{   
    global $_W;
    $mune_mode or $mune_mode = $idtype = $_W['user']['idtype'];

    $data = pdo_fetchall('SELECT id,eid,pid,title FROM dt_mer_menu where is_display = "1" AND pid=0   ORDER BY id ASC');
    $rows = pdo_fetchall('SELECT id,eid,pid,title FROM dt_mer_menu where is_display = "1" ORDER BY id ASC');
    $mers = pdo_fetchall('SELECT id,template_code FROM dt_mer_index_template ORDER BY id ASC');
    foreach($mers as $mer){
        if($mer['id']==$id){
            $mers=$mer;
        }
    }
    $mmc = pdo_fetchall('select menu_id,menu_type,other_code from dt_mer_menu_controler where other_code="'.$mers['template_code'].'" and menu_type="TEMPLATE" ');
    $mmcon=array();
    foreach($mmc as $mm){
        $mmcon[]=$mm['menu_id'];
    }
    

    if (!empty($data)) {
       
        foreach ($data as $key => $item) {
            $childs  = array();
            foreach ($rows as $rkey => $row) {
                if($row){
                    if ($row['pid'] == $item['id']) {
                        $row['actions'] = getAllChilds($rows, $row['id']);
                        $childs[] = $row;
                    }
                }
            }
            $data[$key]['childs'] = $childs;
            $data[$key]['mers'] = $mers;
            $data[$key]['mmc'] = $mmcon;
        }
    }


    return $data;
    
}
function frame_action_menus_fast3_diy1($mune_mode = '', $permission = array(),$id='')
{
    global $_W;
    $mune_mode or $mune_mode = $idtype = $_W['user']['idtype'];

    $data = pdo_fetchall('SELECT id,eid,pid,title FROM dt_mer_menu where pid=0 AND is_display = 1  ORDER BY id ASC');
    $rows = pdo_fetchall('SELECT id,eid,pid,title FROM dt_mer_menu WHERE is_display = 1 ORDER BY id ASC');
    $mers = pdo_fetchall('SELECT id,businesscode FROM dt_mer_business ORDER BY id ASC');
    foreach($mers as $mer){
        if($mer['id']==$id){
            $mers=$mer;
        }
    }
    $mmc = pdo_fetchall('select menu_id,menu_type,other_code from dt_mer_menu_controler where other_code="'.$mers['businesscode'].'" and menu_type="BUSINESS" ');
    $mmcon=array();
    foreach($mmc as $mm){
        $mmcon[]=$mm['menu_id'];
    }


    if (!empty($data)) {

        foreach ($data as $key => $item) {
            $childs  = array();
            foreach ($rows as $rkey => $row) {
                if($row){
                    if ($row['pid'] == $item['id']) {
                        $row['actions'] = getAllChilds($rows, $row['id']);
                        $childs[] = $row;
                    }
                }
            }
            $data[$key]['childs'] = $childs;
            $data[$key]['mers'] = $mers;
            $data[$key]['mmc'] = $mmcon;
        }
    }


    return $data;

}
function frame_action_menus_fast3_diy2($mune_mode = '', $permission = array(),$id='')
{
    global $_W;
    $mune_mode or $mune_mode = $idtype = $_W['user']['idtype'];

    $data = pdo_fetchall('SELECT id,eid,pid,title FROM dt_mer_menu where pid=0 AND is_display = 1  ORDER BY id ASC');
    $rows = pdo_fetchall('SELECT id,eid,pid,title FROM dt_mer_menu WHERE is_display = 1 ORDER BY id ASC');
    $mers = pdo_fetchall('SELECT id FROM dt_mer_role ORDER BY id ASC');
    foreach($mers as $mer){
        if($mer['id']==$id){
            $mers=$mer;
        }
    }
    $mmc = pdo_fetchall('select menu_id,role_id from dt_mer_role_menu where role_id="'.$mers['id'].'" ');
    $mmcon=array();
    foreach($mmc as $mm){
        $mmcon[]=$mm['menu_id'];
    }


    if (!empty($data)) {

        foreach ($data as $key => $item) {
            $childs  = array();
            foreach ($rows as $rkey => $row) {
                if($row){
                    if ($row['pid'] == $item['id']) {
                        $row['actions'] = getAllChilds($rows, $row['id']);
                        $childs[] = $row;
                    }
                }
            }
            $data[$key]['childs'] = $childs;
            $data[$key]['mers'] = $mers;
            $data[$key]['mmc'] = $mmcon;
        }
    }


    return $data;

}
function frame_action_menus_fast3_diy3($mune_mode = '', $permission = array(),$businesscode='')
{
    global $_W;
    $mune_mode or $mune_mode = $idtype = $_W['user']['idtype'];

    $data = pdo_fetchall('SELECT id,eid,pid,title FROM dt_mer_menu where pid=0 AND is_display = 1  ORDER BY id ASC');
    $rows = pdo_fetchall('SELECT id,eid,pid,title FROM dt_mer_menu WHERE is_display = 1 ORDER BY id ASC');
    $mers = pdo_fetchall('SELECT businesscode FROM dt_pub_businesstype ');
    foreach($mers as $mer){
        if($mer['businesscode']==$businesscode){
            $mers=$mer;
        }
    }
    $mmc = pdo_fetchall('select menu_id,menu_type from dt_mer_menu_controler where other_code="'.$mers['businesscode'].'" ');
    $mmcon=array();
    foreach($mmc as $mm){
        $mmcon[]=$mm['menu_id'];
    }


    if (!empty($data)) {

        foreach ($data as $key => $item) {
            $childs  = array();
            foreach ($rows as $rkey => $row) {
                if($row){
                    if ($row['pid'] == $item['id']) {
                        $row['actions'] = getAllChilds($rows, $row['id']);
                        $childs[] = $row;
                    }
                }
            }
            $data[$key]['childs'] = $childs;
            $data[$key]['mers'] = $mers;
            $data[$key]['mmc'] = $mmcon;
        }
    }


    return $data;

}