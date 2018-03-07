<?php
/**
 * [ECOCOM System] Copyright (c) 2014 DTROAD.COM
 * ECOCOM is NOT a free software, it under the license terms, visited http://www.dtroad.com/ for more details.
 */
defined('IN_IA') or exit('Access Denied');


function template_default(array $data = [])
{
    $data and template_assign($data);
    template(get_default_tpl());
}

function get_default_tpl()
{
    global $controller, $action, $do;
    return "{$controller}/{$action}/{$do}";
}

function template_assign($key, $data = null)
{
    if (is_array($key)) {
        $GLOBALS = $key+$GLOBALS;
    } else {
        $GLOBALS[$key] = $data;
    }
}

function template_compat($filename) {
    static $mapping = array(
        'home/home' => 'index',
        'header' => 'common/header',
        'footer' => 'common/footer',
        'footer-base' => 'common/footer-base',
        'slide' => 'common/slide',
    );
    if(!empty($mapping[$filename])) {
        return $mapping[$filename];
    }
    return '';
}
if (!defined('IN_EXP')) {
    function template($filename, $flag = TEMPLATE_DISPLAY, $mode_dir = APP_STATUS) {
        global $_W,$_GPC;
        $template = $_W['template'];
        $source = IA_ROOT . "/{$mode_dir}/themes/{$template}/{$filename}.html";

        if(!is_file($source) && $template != 'default') {
            $template = 'default';
            $source = IA_ROOT . "/{$mode_dir}/themes/default/{$filename}.html";
        }

        if($mode_dir == 'app' && !is_file($source)) {
            $compatFilename = template_compat($filename);
            if(!empty($compatFilename)) {
                return template($compatFilename, $flag, $mode_dir);
            }
        }

        if(!is_file($source)) {
            exit("Error: template source '{$filename}' is not exist!");
        }

        if ($mode_dir == 'app') {
            $t = intval($_GPC['t']);
            $compile = IA_ROOT . "/data/tpl/{$mode_dir}/{$template}/{$_W['uniacid']}_{$t}_{$filename}.tpl.php";
        } else {
            $compile = IA_ROOT . "/data/tpl/{$mode_dir}/{$template}/{$filename}.tpl.php";
        }

        if(DEVELOPMENT || !is_file($compile) || filemtime($source) > filemtime($compile)) {
            template_compile($source, $compile);
        }

        switch ($flag) {
            case TEMPLATE_DISPLAY:
            default:
                extract($GLOBALS, EXTR_SKIP);
                include $compile;
                break;
            case TEMPLATE_FETCH:
                extract($GLOBALS, EXTR_SKIP);
                ob_flush();
                ob_clean();
                ob_start();
                include $compile;
                $contents = ob_get_contents();
                ob_clean();
                return $contents;
                break;
            case TEMPLATE_INCLUDEPATH:
                return $compile;
                break;
        }
    }

    function template_compile($from, $to, $inmodule = false) {
        $path = dirname($to);
        if (!is_dir($path)) {
            load()->func('file');
            mkdirs($path);
        }
        $content = template_parse(file_get_contents($from), $inmodule);
        file_put_contents($to, $content);
    }

    function template_parse($str, $inmodule = false) {
        $str = preg_replace('/<!--{(.+?)}-->/s', '{$1}', $str);
        if ($inmodule) {
            $str = preg_replace('/{template\s+(.+?)}/', '<?php include $this->template($1, TEMPLATE_INCLUDEPATH);?>', $str);
        } else {
            $str = preg_replace('/{template\s+(.+?)}/', '<?php (!empty($this) && $this instanceof \\WeModuleSite) ? (include $this->template($1, TEMPLATE_INCLUDEPATH)) : (include template($1, TEMPLATE_INCLUDEPATH));?>', $str);
        }
        $str = preg_replace_callback('/{json:(\\$[a-zA-Z_][a-zA-Z0-9_]*(?:\\[\'[a-zA-Z0-9_]*\'\\]|\\["[a-zA-Z0-9_]*"\\])*)(?:(\\.|->)([a-zA-Z_][a-zA-Z0-9_]*)(\\(.*\\)))?}/', function($matches){
            $valall = $val = $matches[1];
            if (isset($matches[2])) {
                $valall = $val.'->'.$matches[3].$matches[4];
            }
            return '<?php echo json_encode(isset('.$val.')?'.$valall.':null, JSON_UNESCAPED_UNICODE);?>';
        }, $str);
        $str = preg_replace('/{C:([a-zA-Z_][a-zA-Z0-9_]*)}/', '<?php echo C(\'$1\');?>', $str);
        $str = preg_replace('/{:([a-zA-Z_][a-zA-Z0-9_]*)\\((.*)\\)}/', '<?php echo $1($2);?>', $str);
        $str = preg_replace('/{php\s+(.+?)}/', '<?php $1?>', $str);
        $str = preg_replace('/{if\s+(.+?)}/', '<?php if($1) { ?>', $str);
        $str = preg_replace('/{else}/', '<?php } else { ?>', $str);
        $str = preg_replace('/{else ?if\s+(.+?)}/', '<?php } else if($1) { ?>', $str);
        $str = preg_replace('/{\/if}/', '<?php } ?>', $str);
        $str = preg_replace('/{loop\s+(\S+)\s+(\S+)}/', '<?php if(is_array($1)) { foreach($1 as $2) { ?>', $str);
        $str = preg_replace('/{loop\s+(\S+)\s+(\S+)\s+(\S+)}/', '<?php if(is_array($1)) { foreach($1 as $2 => $3) { ?>', $str);
        $str = preg_replace('/{\/loop}/', '<?php } } ?>', $str);
        /*$str = preg_replace('/{(\$[a-zA-Z_][a-zA-Z0-9_]*)}/', '<?php echo $1;?>', $str);
        $str = preg_replace('/{(\$[a-zA-Z_][a-zA-Z0-9_\[\]\'\"\$]*)}/', '<?php echo $1;?>', $str);
        */
        $str = preg_replace_callback('/{(\\$[a-zA-Z_][a-zA-Z0-9_]*(?:\\[\'[a-zA-Z0-9_]*\'\\]|\\["[a-zA-Z0-9_]*"\\]|\\[[0-9]+\\])*)(?:(\\.|->)([a-zA-Z_][a-zA-Z0-9_]*)(\\(.*\\)))?}/', function($matches){
            $valall = $val = $matches[1];
            if (isset($matches[2])) {
                $valall = $val.'->'.$matches[3].$matches[4];
            }
            return '<?php echo isset('.$val.')?'.$valall.':"";?>';
        }, $str);

        $str = preg_replace('/{(\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\[\]\'\"\$]*)}/', '<?php echo $1;?>', $str);

        $str = preg_replace('/{url\s+(\S+)}/', '<?php echo url($1);?>', $str);
        $str = preg_replace('/{url\s+(\S+)\s+(array\(.+?\))}/', '<?php echo url($1, $2);?>', $str);
        $str = preg_replace('/{media\s+(\S+)}/', '<?php echo tomedia($1);?>', $str);

        if (APP_STATUS == 'app') {
            $str = preg_replace_callback('/{data\s+(.+?)}/s', "moduledata", $str);
            $str = preg_replace('/{\/data}/', '<?php } } ?>', $str);
        }
        $str = preg_replace_callback('/<\?php([^\?]+)\?>/s', "template_addquote", $str);
        $str = preg_replace('/{([A-Z_][A-Z0-9_]*)}/s', '<?php echo $1;?>', $str);
        $str = str_replace('{##', '{', $str);
        $str = str_replace('##}', '}', $str);
        /*if (!empty($GLOBALS['_W']['setting']['remote']['type'])) {
            $str = str_replace('</body>', "<script>\$(function(){\$('img').attr('onerror', '').on('error', function(){if (!\$(this).data('check-src') && (this.src.indexOf('http://') > -1 || this.src.indexOf('https://') > -1)) {this.src = this.src.indexOf('{$GLOBALS['_W']['attachurl_local']}') == -1 ? this.src.replace('{$GLOBALS['_W']['attachurl_remote']}', '{$GLOBALS['_W']['attachurl_local']}') : this.src.replace('{$GLOBALS['_W']['attachurl_local']}', '{$GLOBALS['_W']['attachurl_remote']}');\$(this).data('check-src', true);}});});</script></body>", $str);
        }*/
        $str = "<?php defined('IN_IA') or exit('Access Denied');?>" . $str;
        return $str;
    }

    function template_addquote($matchs) {
        $code = "<?php {$matchs[1]}?>";
        $code = preg_replace('/\[([a-zA-Z0-9_\-\.]+)\](?![a-zA-Z0-9_\-\.\[\]]*[\'"])/s', "['$1']", $code);
        return str_replace('\\\"', '\"', $code);
    }
}



