<?php
/**
 * [ECOCOM System] Copyright (c) 2014 DTROAD.COM
 * ECOCOM is NOT a free software, it under the license terms, visited http://www.dtroad.com/ for more details.
 */
defined('IN_IA') or exit('Access Denied');


if (APP_STATUS == 'app') {
	load()->func('app,tpl');
} elseif(APP_STATUS == 'mp'){
    load()->func('mp.tpl');
} else {
	load()->func('web.tpl');
}


function tpl_form_field_date($name, $value = '', $withtime = false, $min_interval=5) { //默认5分钟
	return _tpl_form_field_date($name, $value, $withtime, $min_interval);
}

function tpl_form_field_clock($name, $value = '') {
	$s = '';
	if(!defined('TPL_INIT_CLOCK_TIME')) {
		$s .= '
		<script type="text/javascript">
			require(["clockpicker"], function($){
				$(function(){
					$(".clockpicker").clockpicker({
						autoclose: true
					});
				});
			});
		</script>
		';
		define('TPL_INIT_CLOCK_TIME', 1);
	}
	$time = date('H:i');
	if(!empty($value)) {
		if(!strexists($value, ':')) {
			$time = date('H:i', $value);
		} else {
			$time = $value;
		}
	}
	$s .= '	<div class="input-group clockpicker">
				<span class="input-group-addon"><i class="fa fa-clock-o"></i></span>
				<input type="text" name="'.$name.'" value="'.$time.'" class="form-control">
			</div>';
	return $s;
}


function tpl_form_field_daterange($name, $value = array(), $time = false,$dateempty = array()) {
	$s = '';
	if (empty($time) && !defined('TPL_INIT_DATERANGE_DATE')) {
		$s = '
<script type="text/javascript">
	require(["daterangepicker"], function($){
		$(function(){
			$(".daterange.daterange-date").each(function(){
				var elm = this;
				$(this).daterangepicker({
					startDate: $(elm).prev().prev().val(),
					endDate: $(elm).prev().val(),
					format: "YYYY-MM-DD",
					showDropdowns:true
				}, function(start, end){
					$(elm).find(".date-title").html(start.toDateStr() + " 至 " + end.toDateStr());
					$(elm).prev().prev().val(start.toDateStr());
					$(elm).prev().val(end.toDateStr());
				});
			});
		});
	});
</script>
';
		define('TPL_INIT_DATERANGE_DATE', true);
	}

	if (!empty($time) && !defined('TPL_INIT_DATERANGE_TIME')) {
		$s = '
<script type="text/javascript">
	require(["daterangepicker"], function($){
		$(function(){
			$(".daterange.daterange-time").each(function(){
				var elm = this;
				$(this).daterangepicker({
					startDate: $(elm).prev().prev().val(),
					endDate: $(elm).prev().val(),
					format: "YYYY-MM-DD HH:mm",
					timePicker: true,
					timePicker12Hour : false,
					timePickerIncrement: 1,
					minuteStep: 1,
					showDropdowns:true
				}, function(start, end){
					$(elm).find(".date-title").html(start.toDateTimeStr() + " 至 " + end.toDateTimeStr());
					$(elm).prev().prev().val(start.toDateTimeStr());
					$(elm).prev().val(end.toDateTimeStr());
				});
			});
		});
	});
</script>
';
		define('TPL_INIT_DATERANGE_TIME', true);
	}

	if($value['start']) {
		$value['starttime'] = empty($time) ? date('Y-m-d',strtotime($value['start'])) : date('Y-m-d H:i',strtotime($value['start']));
	}
	if($value['end']) {
		$value['endtime'] = empty($time) ? date('Y-m-d',strtotime($value['end'])) : date('Y-m-d H:i',strtotime($value['end']));
	}
	$value['starttime'] = empty($value['starttime']) ? (empty($time) ? date('Y-m-d') : date('Y-m-d H:i') ): $value['starttime'];
	$value['endtime'] = empty($value['endtime']) ? $value['starttime'] : $value['endtime'];
	$s .= '
	<input name="'.$name . '[start]'.'" type="hidden" value="'. $value['starttime'].'" />
	<input name="'.$name . '[end]'.'" type="hidden" value="'. $value['endtime'].'" />
	<button class="btn btn-default daterange '.(!empty($time) ? 'daterange-time' : 'daterange-date').'" type="button"><span class="date-title">'.$value['starttime'].' 至 '.$value['endtime'].'</span> <i class="fa fa-calendar"></i></button>
	';
	return $s;
}


function tpl_form_field_calendar($name, $values = array()) {
	$html = '';
	if (!defined('TPL_INIT_CALENDAR')) {
		$html .= '
		<script type="text/javascript">
			function handlerCalendar(elm) {
				require(["jquery","moment"], function($, moment){
					var tpl = $(elm).parent().parent();
					var year = tpl.find("select.tpl-year").val();
					var month = tpl.find("select.tpl-month").val();
					var day = tpl.find("select.tpl-day");
					day[0].options.length = 1;
					if(year && month) {
						var date = moment(year + "-" + month, "YYYY-M");
						var days = date.daysInMonth();
						for(var i = 1; i <= days; i++) {
							var opt = new Option(i, i);
							day[0].options.add(opt);
						}
						if(day.attr("data-value")!=""){
							day.val(day.attr("data-value"));
						} else {
							day[0].options[0].selected = "selected";
						}
					}
				});
			}
			require(["jquery"], function($){
				$(".tpl-calendar").each(function(){
					handlerCalendar($(this).find("select.tpl-year")[0]);
				});
			});
		</script>';
		define('TPL_INIT_CALENDAR', true);
	}

	if (empty($values) || !is_array($values)) {
		$values = array(0,0,0);
	}
	$values['year'] = intval($values['year']);
	$values['month'] = intval($values['month']);
	$values['day'] = intval($values['day']);

	if (empty($values['year'])) {
		$values['year'] = '1980';
	}
	$year = array(date('Y'), '1914');
	$html .= '<div class="row row-fix tpl-calendar">
		<div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
			<select name="' . $name . '[year]" onchange="handlerCalendar(this)" class="form-control tpl-year">
				<option value="">年</option>';
	for ($i = $year[1]; $i <= $year[0]; $i++) {
		$html .= '<option value="' . $i . '"' . ($i == $values['year'] ? ' selected="selected"' : '') . '>' . $i . '</option>';
	}
	$html .= '	</select>
		</div>
		<div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
			<select name="' . $name . '[month]" onchange="handlerCalendar(this)" class="form-control tpl-month">
				<option value="">月</option>';
	for ($i = 1; $i <= 12; $i++) {
		$html .= '<option value="' . $i . '"' . ($i == $values['month'] ? ' selected="selected"' : '') . '>' . $i . '</option>';
	}
	$html .= '	</select>
		</div>
		<div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
			<select name="' . $name . '[day]" data-value="' . $values['day'] . '" class="form-control tpl-day">
				<option value="0">日</option>
			</select>
		</div>
	</div>';
	return $html;
}


function tpl_form_field_district($name, $values = array()) {
	$html = '';
	if (!defined('TPL_INIT_DISTRICT')) {
		$html .= '
		<script type="text/javascript">
			require(["jquery", "district"], function($, dis){
				$(".tpl-district-container").each(function(){
					var elms = {};
					elms.province = $(this).find(".tpl-province")[0];
					elms.city = $(this).find(".tpl-city")[0];
					elms.district = $(this).find(".tpl-district")[0];
					var vals = {};
					vals.province = $(elms.province).attr("data-value");
					vals.city = $(elms.city).attr("data-value");
					vals.district = $(elms.district).attr("data-value");
					dis.render(elms, vals, {withTitle: true});
				});
			});
		</script>';
		define('TPL_INIT_DISTRICT', true);
	}
	if (empty($values) || !is_array($values)) {
		$values = array('province'=>'','city'=>'','district'=>'');
	}
	if(empty($values['province'])) {
		$values['province'] = '';
	}
	if(empty($values['city'])) {
		$values['city'] = '';
	}
	if(empty($values['district'])) {
		$values['district'] = '';
	}
	$html .= '
		<div class="row row-fix tpl-district-container">
			<div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
				<select name="' . $name . '[province]" data-value="' . $values['province'] . '" class="form-control tpl-province">
				</select>
			</div>
			<div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
				<select name="' . $name . '[city]" data-value="' . $values['city'] . '" class="form-control tpl-city">
				</select>
			</div>
			<div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
				<select name="' . $name . '[district]" data-value="' . $values['district'] . '" class="form-control tpl-district">
				</select>
			</div>
		</div>';
	return $html;
}


function tpl_form_field_category_2level($name, $parents, $children, $parentid, $childid){
	$html = '
		<script type="text/javascript">
			window._' . $name . ' = ' . json_encode($children) . ';
		</script>';
			if (!defined('TPL_INIT_CATEGORY')) {
				$html .= '
		<script type="text/javascript">
			function renderCategory(obj, name){
				var index = obj.options[obj.selectedIndex].value;
				require([\'jquery\', \'util\'], function($, u){
					$selectChild = $(\'#\'+name+\'_child\');
					var html = \'<option value="0">请选择二级分类</option>\';
					if (!window[\'_\'+name] || !window[\'_\'+name][index]) {
						$selectChild.html(html);
						return false;
					}
					for(var i=0; i< window[\'_\'+name][index].length; i++){
						html += \'<option value="\'+window[\'_\'+name][index][i][\'id\']+\'">\'+window[\'_\'+name][index][i][\'name\']+\'</option>\';
					}
					$selectChild.html(html);
				});
			}
		</script>
					';
				define('TPL_INIT_CATEGORY', true);
			}

			$html .=
				'<div class="row row-fix tpl-category-container">
			<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
				<select class="form-control tpl-category-parent" id="' . $name . '_parent" name="' . $name . '[parentid]" onchange="renderCategory(this,\'' . $name . '\')">
					<option value="0">请选择一级分类</option>';
			$ops = '';
			foreach ($parents as $row) {
				$html .= '
					<option value="' . $row['id'] . '" ' . (($row['id'] == $parentid) ? 'selected="selected"' : '') . '>' . $row['name'] . '</option>';
			}
			$html .= '
				</select>
			</div>
			<div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
				<select class="form-control tpl-category-child" id="' . $name . '_child" name="' . $name . '[childid]">
					<option value="0">请选择二级分类</option>';
			if (!empty($parentid) && !empty($children[$parentid])) {
				foreach ($children[$parentid] as $row) {
					$html .= '
					<option value="' . $row['id'] . '"' . (($row['id'] == $childid) ? 'selected="selected"' : '') . '>' . $row['name'] . '</option>';
				}
			}
			$html .= '
				</select>
			</div>
		</div>
	';
	return $html;
}


function tpl_form_field_industry($name, $pvalue = '', $cvalue = '', $parentid = 'industry_1', $childid = 'industry_2'){
	$html = '
	<div class="row row-fix">
		<div class="col-sm-4">
			<select name="' . $name . '[parent]" id="' . $parentid . '" class="form-control" value="' . $pvalue . '"></select>
		</div>
		<div class="col-sm-4">
			<select name="' . $name . '[child]" id="' . $childid . '" class="form-control" value="' . $cvalue . '"></select>
		</div>
		<script type="text/javascript">
			require([\'industry\'], function(industry){
				industry.init("'. $parentid . '","' . $childid . '");
			});
		</script>
	</div>';
	return $html;
}


function tpl_form_field_coordinate($field, $value = array()) {
	$s = '';
	if(!defined('TPL_INIT_COORDINATE')) {
		$s .= '<script type="text/javascript">
				function showCoordinate(elm) {
					require(["util"], function(util){
						var val = {};
						val.lng = parseFloat($(elm).parent().prev().prev().find(":text").val());
						val.lat = parseFloat($(elm).parent().prev().find(":text").val());
						util.map(val, function(r){
							$(elm).parent().prev().prev().find(":text").val(r.lng);
							$(elm).parent().prev().find(":text").val(r.lat);
						});

					});
				}

			</script>';
		define('TPL_INIT_COORDINATE', true);
	}
	$s .= '
		<div class="row row-fix">
			<div class="col-xs-4 col-sm-4">
				<input type="text" name="' . $field . '[lng]" value="'.$value['lng'].'" placeholder="地理经度"  class="form-control" />
			</div>
			<div class="col-xs-4 col-sm-4">
				<input type="text" name="' . $field . '[lat]" value="'.$value['lat'].'" placeholder="地理纬度"  class="form-control" />
			</div>
			<div class="col-xs-4 col-sm-4">
				<button onclick="showCoordinate(this);" class="btn btn-default" type="button">选择坐标</button>
			</div>
		</div>';
	return $s;
}


function tpl_fans_form($field, $value = '') {
	switch ($field) {
	case 'avatar':
		$avatar_url = '../attachment/images/global/avatars/';
		$html = '';
		if (!defined('TPL_INIT_AVATAR')) {
			$html .= '
			<script type="text/javascript">
				function showAvatarDialog(elm, opts) {
					require(["util"], function(util){
						var btn = $(elm);
						var ipt = btn.parent().prev();
						var img = ipt.parent().next().children();
						var content = \'<div class="avatar-browser clearfix">\';
						for(var i = 1; i <= 12; i++) {
							content +=
								\'<div title="头像\' + i + \'" class="thumbnail">\' +
									\'<em><img src="' . $avatar_url . 'avatar_\' + i + \'.jpg" class="img-responsive"></em>\' +
								\'</div>\';
						}
						content += "</div>";
						var dialog = util.dialog("请选择头像", content);
						dialog.modal("show");
						dialog.find(".thumbnail").on("click", function(){
							var url = $(this).find("img").attr("src");
							img.get(0).src = url;
							ipt.val(url.replace(/^\.\.\/attachment\//, ""));
							dialog.modal("hide");
						});
					});
				}
			</script>';
			define('TPL_INIT_AVATAR', true);
		}
		if (!defined('TPL_INIT_IMAGE')) {
			global $_W;
			if (defined('IN_MOBILE')) {
				$html .= <<<EOF
				<script type="text/javascript">
					// in mobile
					function showImageDialog(elm) {
						require(["jquery", "util"], function($, util){
							var btn = $(elm);
							var ipt = btn.parent().prev();
							var val = ipt.val();
							var img = ipt.parent().next().children();
							util.image(elm, function(url){
								img.get(0).src = url.url;
								ipt.val(url.attachment);
							});
						});
					}
				</script>
EOF;
			} else {
				$html .= <<<EOF
				<script type="text/javascript">
					// in web
					function showImageDialog(elm, opts) {
						require(["util"], function(util){
							var btn = $(elm);
							var ipt = btn.parent().prev();
							var val = ipt.val();
							var img = ipt.parent().next().find('img');
							util.image(val, function(url){
								img.get(0).src = url.url;
								ipt.val(url.attachment);
							}, opts, {multiple:false,type:"image",direct:true});
						});
					}
				</script>
EOF;
			}
			define('TPL_INIT_IMAGE', true);
		}
		$val = './resource/images/nopic.jpg';
		if (!empty($value)) {
			$val = tomedia($value);
		}
		$options = array();
		$options['width'] = '200';
		$options['height'] = '200';

		if (defined('IN_MOBILE')) {
			$html .= <<<EOF
			<div class="input-group">
				<input type="text" value="{$value}" name="{$field}" class="form-control" autocomplete="off">
				<span class="input-group-btn">
					<button class="btn btn-default" type="button" onclick="showImageDialog(this);">选择图片</button>
					<button class="btn btn-default" type="button" onclick="showAvatarDialog(this);">系统头像</button>
				</span>
			</div>
			<div class="input-group" style="margin-top:.5em;">
				<img src="{$val}" class="img-responsive img-thumbnail" width="150" style="max-height: 150px;"/>
			</div>
EOF;
		} else {
			$html .= '
			<div class="input-group">
				<input type="text" value="' . $value . '" name="' . $field . '" class="form-control" autocomplete="off">
				<span class="input-group-btn">
					<button class="btn btn-default" type="button" onclick="showImageDialog(this, \'' . base64_encode(iserializer($options)) . '\');">选择图片</button>
					<button class="btn btn-default" type="button" onclick="showAvatarDialog(this);">系统头像</button>
				</span>
			</div>
			<div class="input-group" style="margin-top:.5em;">
				<img src="' . $val . '" class="img-responsive img-thumbnail" width="150" />
			</div>';
		}

		break;
	case 'birth':
	case 'birthyear':
	case 'birthmonth':
	case 'birthday':
		$html = tpl_form_field_calendar('birth', $value);
		break;
	case 'reside':
	case 'resideprovince':
	case 'residecity':
	case 'residedist':
		$html = tpl_form_field_district('reside', $value);
		break;
	case 'bio':
	case 'interest':
		$html = '<textarea name="' . $field . '" class="form-control">' . $value . '</textarea>';
		break;
	case 'gender':
		$html = '
				<select name="gender" class="form-control">
					<option value="0" ' . ($value == 0 ? 'selected ' : '') . '>保密</option>
					<option value="1" ' . ($value == 1 ? 'selected ' : '') . '>男</option>
					<option value="2" ' . ($value == 2 ? 'selected ' : '') . '>女</option>
				</select>';
		break;
	case 'education':
	case 'constellation':
	case 'zodiac':
	case 'bloodtype':
		if ($field == 'bloodtype') {
			$options = array('A', 'B', 'AB', 'O', '其它');
		} elseif ($field == 'zodiac') {
			$options = array('鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪');
		} elseif ($field == 'constellation') {
			$options = array('水瓶座', '双鱼座', '白羊座', '金牛座', '双子座', '巨蟹座', '狮子座', '处女座', '天秤座', '天蝎座', '射手座', '摩羯座');
		} elseif ($field == 'education') {
			$options = array('博士', '硕士', '本科', '专科', '中学', '小学', '其它');
		}
		$html = '<select name="' . $field . '" class="form-control">';
		foreach ($options as $item) {
			$html .= '<option value="' . $item . '" ' . ($value == $item ? 'selected ' : '') . '>' . $item . '</option>';
		}
		$html .= '</select>';
		break;
	case 'nickname':
	case 'realname':
	case 'address':
	case 'mobile':
	case 'qq':
	case 'msn':
	case 'email':
	case 'telephone':
	case 'taobao':
	case 'alipay':
	case 'studentid':
	case 'grade':
	case 'graduateschool':
	case 'idcard':
	case 'zipcode':
	case 'site':
	case 'affectivestatus':
	case 'lookingfor':
	case 'nationality':
	case 'height':
	case 'weight':
	case 'company':
	case 'occupation':
	case 'position':
	case 'revenue':
	default:
		$html = '<input type="text" class="form-control" name="' . $field . '" value="' . $value . '" />';
		break;
	}
	return $html;
}

/**
 * [tpl_highcharts_curve 曲线图]
 * @author huimin
 * @date   2016-09-26
 * @param  [string] $id [div的id值]
 * @param  [string] $title [标题]
 * @param  [string] $y_title [竖标题]
 * @param  [string] $x_list [横向（日期）列表]
 * @param  [array] $item [数据]
 * @param  [string] $unit [单位]
 * @param  [integer] $width [长]
 * @param  [integer] $height [宽]
 * @return [html&js数据]
 */
function tpl_highcharts_curve($id, $title, $y_title, $x_list, $item, $unit='', $width=1000, $height=400) {//dump($item);
	// 转换数据
	foreach ($item as $k => $v) {
		$data .=  "{name: '$v[name]', ";
		array_shift($v);
		if ($unit == '元') {
			foreach ($v as &$vv) {
				$vv = toYuanPrice($vv);
			}
		}
		$data .=  "data: [".implode(", ", $v)."]}, ";
	}

	/*foreach ($item_1 as $v) {
		$data .=  "{name: '$v[name]', data: [$v[data]]},";
	}*/
	// return $data;
	return "<div id='$id' style='width:".$width."px; height:".$height."px;'></div>
	<script>
	$(function () {
	    $('#$id').highcharts({
	        title: {
	            text: '$title',
	            x: -20 //center
	        },
	        xAxis: {
	            categories: ['$x_list']
	        },
	        yAxis: {
	            title: {
	                text: '$y_title'
	            },
	            plotLines: [{
	                value: 0,
	                width: 1,
	                color: '#808080'
	            }]
	        },
	        tooltip: {
	            valueSuffix: '$unit'
	        },
	        legend: {
	            layout: 'vertical',
	            align: 'right',
	            verticalAlign: 'middle',
	            borderWidth: 0
	        },
	        series: [$data]
	    });
	});
	</script>";

}

/**
 * [tpl_highcharts_columnar 柱形图]
 * @author huimin
 * @date   2016-09-26
 * @param  [string] $id [div的id值]
 * @param  [string] $title [标题]
 * @param  [string] $y_title [竖标题]
 * @param  [string] $x_list [横向（日期）列表]
 * @param  [array] $item [数据]
 * @param  [string] $unit [单位]
 * @param  [string] $type [类型：纵向column，横向bar]
 * @param  [integer] $width [长]
 * @param  [integer] $height [宽]
 * @return [html&js数据]
 */
function tpl_highcharts_columnar($id, $title, $y_title, $x_list, $item, $unit='', $type='column', $width=500, $height=400) {

	foreach ($item as $v) {
		$data .=  "{name: '$v[name]', data: [$v[data]], dataLabels: { enabled: true }},";
	}
	// return $data;
	return "<div id='$id' style='width:".$width."px; height:".$height."px; float:left;'></div>
	<script>
	$(function () { 
	    $('#$id').highcharts({
	        chart: {
	            type: 'column' // 纵向column，横向bar
	        },
	        title: {
	            text: '$title'
	        },
	        xAxis: {
	            categories: ['$y_title']
	        },
	        yAxis: {
	            title: {
	                text: '$x_list'
	            }
	        },
	        tooltip: {
	            valueSuffix: '$unit'
	        },
	        series: [$data]
	    });
	});
	</script>";
}

/**
 * [tpl_highcharts_cooky 饼形图]
 * @author huimin
 * @date   2016-09-26
 * @param  [string] $id [div的id值]
 * @param  [string] $title [标题]
 * @param  [array] $item [数据]
 * @param  [string] $unit [单位]
 * @param  [integer] $width [长]
 * @param  [integer] $height [宽]
 * @return [html&js数据]
 */
function tpl_highcharts_cooky($id, $title, $item, $unit='百分比', $width=500, $height=400) {//dump($item);
	// 转换数据
	foreach ($item as $k => $v) {
		$data .=  "['$v[name]', ";
		array_shift($v);
		if (count($v) > 1) {
			$data .= array_sum($v)."], ";
		} else {
			$data .= $v['data']."], ";
		}
	}

	/*foreach ($item as $v) {
		$data .=  "['$v[name]', $v[data]],";
	}*/
	$data = rtrim($data, ", ") ;
	// return $data;
	return "<div id='$id' style='width:".$width."px; height:".$height."px; float:left;'></div>
	<script>
	$(function () { 
	    $('#$id').highcharts({
	        chart: {
	            plotBackgroundColor: null,
	            plotBorderWidth: null,
	            plotShadow: false
	        },
	        title: {
	            text: '$title'
	        },
	        tooltip: {
	            pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
	        },
	        plotOptions: {
	            pie: {
	                allowPointSelect: true,
	                cursor: 'pointer',
	                dataLabels: {
	                    enabled: false
	                },
	                showInLegend: true
	            }
	        },
	        series: [{
	            type: 'pie',
	            name: '$unit',
	            data: [$data]
	        }]
	    });
	});
	</script>";
}

/**
 * [tpl_highcharts_blend 混合图]
 * @author huimin
 * @date   2016-09-26
 * @param  [string] $id [div的id值]
 * @param  [string] $title [标题]
 * @param  [string] $y_title [竖标题]
 * @param  [string] $x_list [横向（日期）列表]
 * @param  [array] $item [数据]
 * @param  [string] $unit [单位]
 * @param  [integer] $width [长]
 * @param  [integer] $height [宽]
 * @return [html&js数据]
 */
function tpl_highcharts_blend($id, $title, $y_title, $x_list, $item, $unit='', $width=800, $height=400) {//dump($item);
	// 转换数据
	foreach ($item as $k => $v) {
		// 柱形图数据
		// $data .=  "{type: 'column', name: '$v[name]', data: [$v[data]], itemWidth: 20,},";
		$data .=  "{type: 'column', name: '$v[name]', ";

		// 配置饼型图数据
		$item_cooky[$k]['name'] = $v['name'];
		array_shift($v);

		$item_cooky[$k]['data'] = array_sum($v);
		// 配置平均值数据
		foreach ($v as $kk => $vv) {
			$average_data[$kk][] = $vv;
		}

		if ($unit == '元') {
			foreach ($v as &$vv) {
				$vv = toYuanPrice($vv);
			}
		}
		$data .=  "data: [".implode(", ", $v)."], itemWidth: 20,}, ";
	}
	// 平均值数据
	foreach ($average_data as $v) {
		$average .= array_sum($v)/count($v).',';
	}
	$average = rtrim($average, ",") ;
	// var_dump($data, $item_cooky, $average);exit;

	// 曲线图数据
	$data .=  "{type: 'spline', name: '平均值', data: [$average], marker: { lineWidth: 2, lineColor: Highcharts.getOptions().colors[3], fillColor: 'white' }, dataLabels: { enabled: true }, }";
	// return $data;
	return "<div id='$id' style='width:".$width."px; height:".$height."px; float:left;'></div>
	<script>
	$(function () { 
	    $('#$id').highcharts({
            title: {
                text: '$title'
            },
            xAxis: {
                categories: ['$x_list']
            },
            yAxis: {
                title: {
                    text: '$y_title'
                },
                plotLines: [{
                    value: 0,
                    width: 1,
                    color: '#808080'
                }]
            },
            series: [$data]
        });
	});
	</script>".tpl_highcharts_cooky($id.'_cooky', '', $item_cooky, '百分比', $width=200);

}