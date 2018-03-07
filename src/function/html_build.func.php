<?php
use dtwq\html\build\Javascript;

/**
 * html元素构建函数
 * User: kkse
 * Date: 2017/11/4
 * Time: 19:51
 */

function html_build_table_thead_th($th)
{
    is_string($th) and $th = ['title'=>$th];
    $style = '';
    !empty($th['style']) and $style = " style=\"{$th['style']}\"";
    $style or $style = ' style="width:100px"';//默认值

    $th_str = "<th{$style}>{$th['title']}</th>";
    return $th_str;
}

function html_build_table_thead_td($td, $item)
{
    if (is_string($td)) {
        if (strpos($td, ':')) {
            list($field, $convert) = explode(':', $td, 2);
        } else {
            $field = $td;
            $convert = '';
        }

        $td = ['mode'=>'info', 'field'=>$field, 'convert'=>$convert];
    }

    $td_content = '';
    switch ($td['mode']) {
        case 'actions'://a标签动作列表
            $actions = [];
            foreach ($td['actions'] as $action) {
                $actions[] = html_build_table_thead_td_action($action, $item);
            }
            $td_content =  implode(' ',$actions);
            break;
        case 'info':
            $td_content = $item[$td['field']];
            if (is_array($td['convert'])) {
                $td_content = $td['convert'][$td_content]?:'';
            } elseif (is_callable($td['convert'])) {
                $td_content = call_user_func($td['convert'], $td_content);
            }
            break;
        case 'switch':
            $field = $td['field'];
            $key_field = $td['key_field'];

            $checked = $item[$field] == $td['true_val']?' checked':'';
            $td_content = "<input field=\"{$field}\" class=\"switch\" type=\"checkbox\" data=\"{$item[$key_field]}\" {$checked}/>";

            $switchinfo = [
                'find'=>":checkbox[field=\"{$field}\"]",
                'tip'=>empty($td['tip'])?'是否切换?':$td['tip'],
                'url'=>$td['url'],
                'key_field'=>$key_field,
            ];
            get_javascript()->addSwitch($switchinfo, $field);
            break;
        case 'select':
            $field = $td['field'];
            $key_field = htmlspecialchars($td['key_field']);
            $data = htmlspecialchars($item[$key_field]);
            $field_val = htmlspecialchars($item[$field]);

            $options_arr = [];

            foreach ($td['options'] as $key => $name) {
                $selected = $item[$field] == $key?' selected':'';
                $options_arr[] = "<option value=\"{$key}\" {$selected}>{$name}</option>";
            }

            $td_content = "<select field=\"{$field}\" lastval=\"{$field_val}\" data=\"{$data}\">".implode($options_arr)."</select>";
            $ajax_url = url($td['url']);
            $jsfunc = <<<EOT
$('select[field={$field}]').bind('change', function(event){
    var \$this = $(this);
    var id = \$this.attr('data');
    var lastval = \$this.attr('lastval');
    var newval = \$this.val();
    
    $.post('{$ajax_url}', {{$key_field}: id, {$field}: newval}, function(dat) {
        if(dat !== 'success') {
            \$this.val(lastval);
            util.message('操作失败, 请稍后重试. ' + dat);
        } else {
            \$this.attr('lastval', newval);
        }
    });
});
EOT;
            get_javascript()->addUtil()->addList($jsfunc, 'select.'.$field);
            break;

    }

    $style = '';
    !empty($td['style']) and $style = " style=\"{$td['style']}\"";
    $th_str = "<td{$style}>{$td_content}</td>";
    return $th_str;
}

function html_build_table_thead_td_action($action, $item)
{
    $url = url($action['url'], [$action['key']=>$item[$action['key']]]);
    if (!empty($action['tip'])) {
        $onclick = ' onclick="return confirm(\''.$action['tip'].'\'); return false;"';
    } else {
        $onclick = '';
    }

    return "<a{$onclick} href=\"{$url}\" >{$action['title']}</a>";
}

function html_build_form_group($form_group, $item = [])
{
    empty($form_group['mode']) and $form_group['mode'] = 'text';
    $val_str = empty($item[$form_group['name']])?'':$item[$form_group['name']];
    switch ($form_group['mode']) {
        case 'hidden':
            return "<input type=\"hidden\" name=\"{$form_group['name']}\" value=\"{$val_str}\">";
            break;
        case 'dialog_select':
            empty($form_group['text']) and $form_group['text'] = ':';

            get_javascript()->addDialogSelect($form_group, $form_group['name']);
            /*
                ->setOption('switch', true)
                ->setOption('require', 'bootstrap.switch');*/

            //$('[name=copy_id]').data('exp', {'menu_mode':'$("[name=menu_mode]").val()'});

            return <<<EOT
<div class="form-group">
    <label class="col-xs-12 col-sm-2 control-label">{$form_group['title']}</label>
    <div class="col-sm-9 col-xs-12">
        <input class="dialog-select" type="hidden" name="{$form_group['name']}" value="" text="{$form_group['text']}" url="{$form_group['url']}"/>
        <div class="help-block "></div>
    </div>
</div>
EOT;
            break;
        case 'text':
            return <<<EOT
<div class="form-group">
    <label class="col-xs-12 col-sm-2 control-label">{$form_group['title']}</label>
    <div class="col-sm-9 col-xs-12">
        <input type="text" class="form-control" name="{$form_group['name']}" value="{$val_str}">
        <span class="help-block"></span>
    </div>
</div>
EOT;
        break;
        case 'info':
            return <<<EOT
<div class="form-group">
    <label class="col-xs-12 col-sm-2 control-label">{$form_group['title']}</label>
    <div class="col-sm-9 col-xs-12">
        <span class="help-block">{$val_str}</span>
        <div class="help-block"></div>
    </div>
</div>
EOT;
            break;
        case 'switch':
            $checked = $item[$form_group['name']] == $form_group['true_val']?' checked':'';
            get_javascript()->setOption('switch', true)->setOption('require', 'bootstrap.switch');
            return <<<EOT
<div class="form-group">
    <label class="col-xs-12 col-sm-2 control-label">{$form_group['title']}</label>
    <div class="col-sm-9 col-xs-12">
        <input class="switch" name="{$form_group['name']}" type="checkbox" {$checked} value="{$form_group['true_val']}"/>
        <div class="help-block"></div>
    </div>
</div>
EOT;
            break;
        case 'select':
            //data
            /**
             * {loop $rolelist $row}
            <option value="{$row['role_id']}">{$row['name']}</option>
            {/loop}
             */
            $option = ['<option value="">请选择</option>'];
            foreach ($form_group['data'] as $key=>$val) {
                $selected = $val_str == $key?' selected':'';
                $option[] = "<option value=\"{$key}\"{$selected}>{$val}</option>";
            }
            $option_str = implode($option);
            return <<<EOT
<div class="form-group">
    <label class="col-xs-12 col-sm-2 control-label">{$form_group['title']}</label>
    <div class="col-sm-9 col-xs-12">
        <select class="form-control"  name="{$form_group['name']}" title="">
            {$option_str}
        </select>
    </div>
</div>
EOT;
            break;
    }

    return '';
}

function html_build_init_display_screen($screens)
{
    global $controller,$action,$do;
    if (!$screens) {
        return '';
    }

    $item_tpl = <<<'EOT'
<label class="col-xs-12 col-sm-2 control-label">{{title}}</label>
<div class="col-sm-4 col-xs-12">
    <input class="form-control" name="{{name}}" type="text" value="{{value}}">
</div>
EOT;

    $items = [];
    foreach ($screens as $screen) {
        $map = [
            '{{title}}' => $screen['title'],
            '{{name}}' => $screen['field'],
            '{{value}}' => get_gpc($screen['field']),
        ];

        $items[] = strtr($item_tpl, $map);
    }

    $search_item_group = [];
    $item_groups = array_chunk($items, 2);
    $last_group = end($item_groups);
    if (count($last_group) < 2) {
        array_pop($item_groups);
        $search_item_group = $last_group;
    }

    $search_item_group[] = <<<'EOT'
<label class="col-xs-12 col-sm-1 col-md-1 control-label"></label>
<button class="btn btn-primary" name="select" value="select">
    <i class="fa fa-search"></i> 搜索
</button>
EOT;
    $item_groups[] = $search_item_group;

    $form_groups = [];
    foreach ($item_groups as $item_group) {
        $item_group_str = implode($item_group);
        $form_groups[] = "<div class=\"form-group\">{$item_group_str}</div>";
    }
    $form_groups_str = implode($form_groups);

    $screen_html = <<<EOT
<div class="panel panel-info">
	<div class="panel-heading">筛选</div>
	<div class="panel-body">
		<form action="" method="get" class="form-horizontal" role="form">
			<input type="hidden" name="c" value="{$controller}" />
			<input type="hidden" name="a" value="{$action}" />
            <input type="hidden" name="do" value="{$do}" />
			{$form_groups_str}
		</form>
	</div>
</div>
EOT;

    return $screen_html;
}

//加载动态效果
function html_build_javascript()
{
    return strval(get_javascript());
}

/**
 * @return Javascript
 */
function get_javascript()
{
    global $_W;

    return $_W['site']->getJavascript();
}