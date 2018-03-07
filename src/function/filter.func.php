<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/8/31
 * Time: 11:42
 */

function get_filter_white_list($mode, $c, $a, $do)
{
    $white_list_file = PROJECT_PATH ."/{$mode}/config/filter_white/{$c}.{$a}.{$do}.php";
    if (is_file($white_list_file)) {
        $white_list = include ($white_list_file);
    }

    isset($white_list) && is_array($white_list) or $white_list = [];

    return $white_list;
}

function check_fail_filter($var, $key_prefix = '', $white_list = []) {
    if (is_array($var)) {
        foreach ($var as $key => $value) {
            if ($white_list && in_array($key, $white_list)) {
                //在白名单中的字段不检查
                continue;
            }
            check_fail_filter($value, $key_prefix?($key_prefix.".{$key}"):$key);
        }
    } else {
        //这个会导致后台编辑
        //';',
        $list =  ['||','grant','like','table','where','having','drop','select',
            'insert','update','delete','exec','execute','or','and','script','javascript',
            'vbscript','expression','applet','meta','xml','blink','link','embed','object',
            'frame','layer','bgsound','base','onload','onunload','onchange','onsubmit','onreset',
            'onselect','onblur','onfocus','onabort','onkeydown','onkeypress','onkeyup','onclick',
            'ondblclick','onmousedown','onmousemove','onmouseout','onmouseover','onmouseup','onunload'];

        foreach ($list as $val) {

            if (strcasecmp($var, $val) && preg_match('/(^|[^a-zA-Z0-9_])'.preg_quote($val).'($|[^a-zA-Z0-9_])/i', $var)) {
                message("请求数据[{$key_prefix}]含禁用字符串[{$val}],禁止访问", '', 'error');
            }

            //preg_match ( string $pattern , string $subject [, array &$matches [, int $flags = 0 [, int $offset = 0 ]]] )
            //if (stripos($var, $val) !== false) {
            //    message("请求数据[{$key_prefix}]含禁用字符串[{$val}],禁止访问", '', 'error');
            //}
        }

        /**
        $str = strpbrk($var, "|$&;%\"'<>()+\r\n\\");
        if ($str !== false) {
        $fstr = substr(json_encode($str[0]), 1, -1);
        $fstr = str_replace('\\"', '"', $fstr);
        $fstr = str_replace('\\\\', '\\', $fstr);
        message("请求数据[{$key_prefix}]含特殊字符[{$fstr}],禁止访问", '', 'error');
        }
         */
    }
}

function check_referer()
{
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $referer_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
        $pass_list = explode(',', C('PASS_REFERER_LIST', null, $_SERVER['SERVER_NAME']));
        if (!in_array($referer_host, $pass_list)) {
            $check = false;
            $referer_base_host = implode('.', array_slice(explode('.', $referer_host), -2));
            foreach ($pass_list as $pass_host) {
                if (ip2long($pass_host)) {//如果是ip地址就跳过。
                    continue;
                }
                $base_host = implode('.', array_slice(explode('.', $pass_list), -2));
                if ($base_host == $referer_base_host) {
                    $check = true;
                    break;
                }
            }
            if (!$check) {
                \Com\Net\Http::sendHttpStatus(403);
                message("禁止跨域访问", '', 'error');
            }
        }
    }
}

