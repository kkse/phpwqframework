<?php
/** 
 * [ECOCOM System] Copyright (c) 2014 DTROAD.COM
 * ECOCOM is NOT a free software, it under the license terms, visited http://www.dtroad.com/ for more details.
 */
defined('IN_IA') or exit('Access Denied');

//角色筛选
function _tpl_form_filtrate_groups(){
    global $_GPC;
    $where = "WHERE 1";
    $where .= select_rule_where();
    $usergroups = pdo_fetchall('SELECT id, name FROM '.tablename('users_group').$where.' ORDER BY id ASC');
    
    $s = '';
    
    if($usergroups){
        $s .=  '<div class="form-group">
                    <label class="col-xs-12 col-sm-2 col-md-2 control-label">角色</label>
                    <div class="col-sm-8 col-lg-9 col-xs-12">
                        <div class="btn-group">
                            <a href="'.filter_url('groupid:0').'" class="btn ';
                            
        if($_GPC['groupid'] == 0){
            $s .= ' btn-primary"';
        }else{
            $s .= 'btn-default"';
        }   

        $s .=  '>不限</a>';
        
        foreach($usergroups as $row){
            $s .= '<a href="'.filter_url('groupid:' . $row['id']).'" class="btn ';
            
            if($_GPC['groupid'] == $row['id']){
                $s .= ' btn-primary"';
            }else{
                
                $s .= 'btn-default"';
            }
            $s .= ' >'.$row['name'].'</a>';
        }
        
        $s .= '</div>
            </div>
        </div>';
    
    }   
    
    return $s;
}


//根据当前登录管理员显示可选角色权限
function _tpl_form_groups($groupid=0){
    global $_GPC;
    
    $s = '';
    
    $where = "WHERE 1";
    $where .= select_rule_where();
    $usergroups = pdo_fetchall('SELECT id, name FROM '.tablename('users_group').$where.' ORDER BY id ASC');
    
    if($usergroups){
    
        $s .= '<div class="form-group">
                <label class="col-xs-12 col-sm-2 col-md-2 col-lg-2 control-label">所属角色</label>
                <div class="col-sm-10 col-xs-12">';
        
        foreach($usergroups as $row){
            $s .= '<label class="radio-inline"><input type="radio" name="groupid" value="'.$row['id'].'"';
            if($groupid && $groupid == $row['id']){
                $s .= ' checked';
            }
            $s .= ' id="credit1"> '.$row['name'].'</label>';
        }
        $s .= '<div class="help-block"></div>
            </div>
        </div>';
    }
    
    return $s;
}


function _tpl_form_field_date($name, $value = '', $withtime = false, $min_interval = 5) {
	$s = '';
	$s = '
		<script type="text/javascript">
			require(["datetimepicker"], function(){
				$(function(){
						var option = {
							lang : "zh",
							step : '.$min_interval.',
							timepicker : ' . (!empty($withtime) ? "true" : "false") .',
							closeOnDateSelect : true,
							format : "Y-m-d' . (!empty($withtime) ? ' H:i"' : '"') .'
						};
					$(".datetimepicker[name = \'' . $name . '\']").datetimepicker(option);
				});
			});
		</script>';
	$withtime = empty($withtime) ? false : true;
	if (!empty($value)) {
		$value = strexists($value, '-') ? strtotime($value) : $value;
	} else {
		$value = TIMESTAMP;
	}
	$value = ($withtime ? date('Y-m-d H:i:s', $value) : date('Y-m-d', $value));
	$s .= '<input type="text" name="' . $name . '"  value="'.$value.'" placeholder="请选择日期时间" readonly="readonly" class="datetimepicker form-control" style="padding-left:12px;" />';
	return $s;
}


function tpl_form_field_audio($name, $value = '', $options = array()) {
	if (!is_array($options)) {
		$options = array();
	}
	$options['direct'] = true;
	$options['multiple'] = false;
	$s = '';
	if (!defined('TPL_INIT_AUDIO')) {
		$s = '
<script type="text/javascript">
	function showAudioDialog(elm, base64options, options) {
		require(["util"], function(util){
			var btn = $(elm);
			var ipt = btn.parent().prev();
			var val = ipt.val();
			util.audio(val, function(url){
				if(url && url.attachment && url.url){
					btn.prev().show();
					ipt.val(url.attachment);
					ipt.attr("filename",url.filename);
					ipt.attr("url",url.url);
					setAudioPlayer();
				}
				if(url && url.media_id){
					ipt.val(url.media_id);
				}
			}, "" , ' . json_encode($options) . ');
		});
	}

	function setAudioPlayer(){
		require(["jquery", "util", "jquery.jplayer"], function($, u){
			$(function(){
				$(".audio-player").each(function(){
					$(this).prev().find("button").eq(0).click(function(){
						var src = $(this).parent().prev().val();
						if($(this).find("i").hasClass("fa-stop")) {
							$(this).parent().parent().next().jPlayer("stop");
						} else {
							if(src) {
								$(this).parent().parent().next().jPlayer("setMedia", {mp3: u.tomedia(src)}).jPlayer("play");
							}
						}
					});
				});

				$(".audio-player").jPlayer({
					playing: function() {
						$(this).prev().find("i").removeClass("fa-play").addClass("fa-stop");
					},
					pause: function (event) {
						$(this).prev().find("i").removeClass("fa-stop").addClass("fa-play");
					},
					swfPath: "resource/components/jplayer",
					supplied: "mp3"
				});
				$(".audio-player-media").each(function(){
					$(this).next().find(".audio-player-play").css("display", $(this).val() == "" ? "none" : "");
				});
			});
		});
	}
	setAudioPlayer();
</script>';
		echo $s;
		define('TPL_INIT_AUDIO', true);
	}
	$s .= '
	<div class="input-group">
		<input type="text" value="' . $value . '" name="' . $name . '" class="form-control audio-player-media" autocomplete="off" ' . ($options['extras']['text'] ? $options['extras']['text'] : '') . '>
		<span class="input-group-btn">
			<button class="btn btn-default audio-player-play" type="button" style="display:none;"><i class="fa fa-play"></i></button>
			<button class="btn btn-default" type="button" onclick="showAudioDialog(this, \'' . base64_encode(iserializer($options)) . '\',' . str_replace('"', '\'', json_encode($options)) . ');">选择媒体文件</button>
		</span>
	</div>
	<div class="input-group audio-player"></div>';
	return $s;
}


function tpl_form_field_multi_audio($name, $value = array(), $options = array()) {
	$s = '';
	$options['direct'] = false;
	$options['multiple'] = true;

	if (!defined('TPL_INIT_MULTI_AUDIO')) {
		$s .= '
<script type="text/javascript">
	function showMultiAudioDialog(elm, name) {
		require(["util"], function(util){
			var btn = $(elm);
			var ipt = btn.parent().prev();
			var val = ipt.val();

			util.audio(val, function(urls){
				$.each(urls, function(idx, url){
					var obj = $(\'<div class="multi-audio-item" style="height: 40px; position:relative; float: left; margin-right: 18px;"><div class="multi-audio-player"></div><div class="input-group"><input type="text" class="form-control" readonly value="\' + url.attachment + \'" /><div class="input-group-btn"><button class="btn btn-default" type="button"><i class="fa fa-play"></i></button><button class="btn btn-default" onclick="deleteMultiAudio(this)" type="button"><i class="fa fa-remove"></i></button></div></div><input type="hidden" name="\'+name+\'[]" value="\'+url.attachment+\'"></div>\');
					$(elm).parent().parent().next().append(obj);
					setMultiAudioPlayer(obj);
				});
			}, "" , ' . json_encode($options) . ');
		});
	}
	function deleteMultiAudio(elm){
		require([\'jquery\'], function($){
			$(elm).parent().parent().parent().remove();
		});
	}
	function setMultiAudioPlayer(elm){
		require(["jquery", "util", "jquery.jplayer"], function($, u){
			$(".multi-audio-player",$(elm)).next().find("button").eq(0).click(function(){
				var src = $(this).parent().prev().val();
				if($(this).find("i").hasClass("fa-stop")) {
					$(this).parent().parent().prev().jPlayer("stop");
				} else {
					if(src) {
						$(this).parent().parent().prev().jPlayer("setMedia", {mp3: u.tomedia(src)}).jPlayer("play");
					}
				}
			});
			$(".multi-audio-player",$(elm)).jPlayer({
				playing: function() {
					$(this).next().find("i").eq(0).removeClass("fa-play").addClass("fa-stop");
				},
				pause: function (event) {
					$(this).next().find("i").eq(0).removeClass("fa-stop").addClass("fa-play");
				},
				swfPath: "resource/components/jplayer",
				supplied: "mp3"
			});
		});
	}
</script>';
		define('TPL_INIT_MULTI_AUDIO', true);
	}

	$s .= '
<div class="input-group">
	<input type="text" class="form-control" readonly="readonly" value="" placeholder="批量上传音乐" autocomplete="off">
	<span class="input-group-btn">
		<button class="btn btn-default" type="button" onclick="showMultiAudioDialog(this,\'' . $name . '\');">选择音乐</button>
	</span>
</div>
<div class="input-group multi-audio-details clear-fix" style="margin-top:.5em;">';
	if (!empty($value) && !is_array($value)) {
		$value = array($value);
	}
	if (is_array($value) && count($value) > 0) {
		$n = 0;
		foreach ($value as $row) {
			$m = random(8);
			$s .= '
	<div class="multi-audio-item multi-audio-item-' . $n . '-' . $m . '" style="height: 40px; position:relative; float: left; margin-right: 18px;">
		<div class="multi-audio-player"></div>
		<div class="input-group">
			<input type="text" class="form-control" value="' . $row . '" readonly/>
			<div class="input-group-btn">
				<button class="btn btn-default" type="button"><i class="fa fa-play"></i></button>
				<button class="btn btn-default" onclick="deleteMultiAudio(this)" type="button"><i class="fa fa-remove"></i></button>
			</div>
		</div>
		<input type="hidden" name="' . $name . '[]" value="' . $row . '">
	</div>
	<script language="javascript">setMultiAudioPlayer($(".multi-audio-item-' . $n . '-' . $m . '"));</script>';
			$n++;
		}
	}
	$s .= '
</div>';

	return $s;
}


function tpl_form_field_link($name, $value = '', $options = array()) {
	global $_GPC;
	$s = '';
	if (!defined('TPL_INIT_LINK')) {
		$s = '
		<script type="text/javascript">
			function showLinkDialog(elm) {
				require(["util","jquery"], function(u, $){
					var ipt = $(elm).parent().parent().parent().prev();
					u.linkBrowser(function(href){
						var multiid = "'. $_GPC['multiid'] .'";
						if (multiid) {
							href = /(&)?t=/.test(href) ? href : href + "&t=" + multiid;
						}
						ipt.val(href);
					});
				});
			}
			function newsLinkDialog(elm, page) {
				require(["util","jquery"], function(u, $){
					var ipt = $(elm).parent().parent().parent().prev();
					u.newsBrowser(function(href, page){
						if (page != "" && page != undefined) {
							newsLinkDialog(elm, page);
							return false;
						}
						var multiid = "'. $_GPC['multiid'] .'";
						if (multiid) {
							href = /(&)?t=/.test(href) ? href : href + "&t=" + multiid;
						}
						ipt.val(href);
					}, page);
				});
			}
			function pageLinkDialog(elm, page) {
				require(["util","jquery"], function(u, $){
					var ipt = $(elm).parent().parent().parent().prev();
					u.pageBrowser(function(href, page){
						if (page != "" && page != undefined) {
							pageLinkDialog(elm, page);
							return false;
						}
						var multiid = "'. $_GPC['multiid'] .'";
						if (multiid) {
							href = /(&)?t=/.test(href) ? href : href + "&t=" + multiid;
						}
						ipt.val(href);
					}, page);
				});
			}
			function articleLinkDialog(elm, page) {
				require(["util","jquery"], function(u, $){
					var ipt = $(elm).parent().parent().parent().prev();
					u.articleBrowser(function(href, page){
						if (page != "" && page != undefined) {
							articleLinkDialog(elm, page);
							return false;
						}
						var multiid = "'. $_GPC['multiid'] .'";
						if (multiid) {
							href = /(&)?t=/.test(href) ? href : href + "&t=" + multiid;
						}
						ipt.val(href);
					}, page);
				});
			}
			function phoneLinkDialog(elm, page) {
				require(["util","jquery"], function(u, $){
					var ipt = $(elm).parent().parent().parent().prev();
					u.phoneBrowser(function(href, page){
						if (page != "" && page != undefined) {
							phoneLinkDialog(elm, page);
							return false;
						}
						ipt.val(href);
					}, page);
				});
			}
			function mapLinkDialog(elm) {
				require(["util","jquery"], function(u, $){
					var ipt = $(elm).parent().parent().parent().prev();
					u.map(elm, function(val){
						var href = \'//api.map.baidu.com/marker?location=\'+val.lat+\',\'+val.lng+\'&output=html&src=we7\';
						var multiid = "'. $_GPC['multiid'] .'";
						if (multiid) {
							href = /(&)?t=/.test(href) ? href : href + "&t=" + multiid;
						}
						ipt.val(href);
					});
				});
			}
		</script>';
		define('TPL_INIT_LINK', true);
	}
	$s .= '
	<div class="input-group">
		<input type="text" value="'.$value.'" name="'.$name.'" class="form-control" autocomplete="off">
		<span class="input-group-btn">
			<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" type="button" aria-haspopup="true" aria-expanded="false">选择链接 <span class="caret"></span></button>
			<ul class="dropdown-menu">
				<li><a href="javascript:" data-type="system" onclick="showLinkDialog(this);">系统菜单</a></li>
				<li><a href="javascript:" data-type="page" onclick="pageLinkDialog(this);">微页面</a></li>
				<li><a href="javascript:" data-type="article" onclick="articleLinkDialog(this)">文章及分类</a></li>
				<li><a href="javascript:" data-type="news" onclick="newsLinkDialog(this)">图文回复</a></li>
				<li><a href="javascript:" data-type="map" onclick="mapLinkDialog(this)">一键导航</a></li>
				<li><a href="javascript:" data-type="phone" onclick="phoneLinkDialog(this)">一键拨号</a></li>
			</ul>
		</span>
	</div>
	';
	return $s;
}


function tpl_form_module_link($name) {
	$s = '';
	if (!defined('TPL_INIT_module')) {
		$s = '
		<script type="text/javascript">
			function showModuleLink(elm) {
				require(["util","jquery"], function(u, $){
					u.showModuleLink(function(href, permission) {
						var ipt = $(elm).parent().prev();
						var ipts = $(elm).parent().prev().prev();
						ipt.val(href);
						ipts.val(permission);
						});
					});
				}
		</script>';
		define('TPL_INIT_module', true);
	}
	$s .= '
	<div class="input-group">
		<input type="text" class="form-control" name="permission" style="display: none">
		<input type="text" class="form-control" name="'.$name.'">
			<span class="input-group-btn">
				<a href="javascript:"  class="btn btn-default" onclick="showModuleLink(this)">选择链接</a>
			</span>
	</div>
	';
	return $s;
}


function tpl_form_field_emoji($name, $value = '') {
	$s = '';
	if (!defined('TPL_INIT_EMOJI')) {
		$s = '
		<script type="text/javascript">
			function showEmojiDialog(elm) {
				require(["util", "jquery"], function(u, $){
					var btn = $(elm);
					var spview = btn.parent().prev();
					var ipt = spview.prev();
					if(!ipt.val()){
						spview.css("display","none");
					}
					u.emojiBrowser(function(emoji){
						ipt.val("\\\" + emoji.find("span").text().replace("+", "").toLowerCase());
						spview.show();
						spview.find("span").removeClass().addClass(emoji.find("span").attr("class"));
					});
				});
			}
		</script>';
		define('TPL_INIT_EMOJI', true);
	}
	$s .= '
	<div class="input-group" style="width: 500px;">
		<input type="text" value="' . $value . '" name="' . $name . '" class="form-control" autocomplete="off">
		<span class="input-group-addon" style="display:none"><span></span></span>
		<span class="input-group-btn">
			<button class="btn btn-default" type="button" onclick="showEmojiDialog(this);">选择表情</button>
		</span>
	</div>
	';
	return $s;
}


function tpl_form_field_color($name, $value = '') {
	$s = '';
	if (!defined('TPL_INIT_COLOR')) {
		$s = '
		<script type="text/javascript">
			require(["jquery", "util"], function($, util){
				$(function(){
					$(".colorpicker").each(function(){
						var elm = this;
						util.colorpicker(elm, function(color){
							$(elm).parent().prev().prev().val(color.toHexString());
							$(elm).parent().prev().css("background-color", color.toHexString());
						});
					});
					$(".colorclean").click(function(){
						$(this).parent().prev().prev().val("");
						$(this).parent().prev().css("background-color", "#FFF");
					});
				});
			});
		</script>';
		define('TPL_INIT_COLOR', true);
	}
	$s .= '
		<div class="row row-fix">
			<div class="col-xs-8 col-sm-8" style="padding-right:0;">
				<div class="input-group">
					<input class="form-control" type="text" name="'.$name.'" placeholder="请选择颜色" value="'.$value.'">
					<span class="input-group-addon" style="width:35px;border-left:none;background-color:'.$value.'"></span>
					<span class="input-group-btn">
						<button class="btn btn-default colorpicker" type="button">选择颜色 <i class="fa fa-caret-down"></i></button>
						<button class="btn btn-default colorclean" type="button"><span><i class="fa fa-remove"></i></span></button>
					</span>
				</div>
			</div>
		</div>
		';
	return $s;
}


function tpl_form_field_icon($name, $value='') {
	if(empty($value)){
		$value = 'fa fa-external-link';
	}
	$s = '';
	if (!defined('TPL_INIT_ICON')) {
		$s = '
		<script type="text/javascript">
			function showIconDialog(elm) {
				require(["util","jquery"], function(u, $){
					var btn = $(elm);
					var spview = btn.parent().prev();
					var ipt = spview.prev();
					if(!ipt.val()){
						spview.css("display","none");
					}
					u.iconBrowser(function(ico){
						ipt.val(ico);
						spview.show();
						spview.find("i").attr("class","");
						spview.find("i").addClass("fa").addClass(ico);
					});
				});
			}
		</script>';
		define('TPL_INIT_ICON', true);
	}
	$s .= '
	<div class="input-group" style="width: 300px;">
		<input type="text" value="'.$value.'" name="'.$name.'" class="form-control" autocomplete="off">
		<span class="input-group-addon"><i class="'.$value.' fa"></i></span>
		<span class="input-group-btn">
			<button class="btn btn-default" type="button" onclick="showIconDialog(this);">选择图标</button>
		</span>
	</div>
	';
	return $s;
}


function tpl_form_field_image($name, $value = '', $default = '', $options = array(),$del=false) {
    global $_W;
    if (empty($default)) {
        $default = './resource/images/nopic.jpg';
    }
    $val = $default;
    if (!empty($value)) {
        $val = tomedia($value);
    }
    if (!empty($options['global'])) {
        $options['global'] = true;
    } else {
        $options['global'] = false;
    }
    if (empty($options['class_extra'])) {
        $options['class_extra'] = '';
    }
    if (isset($options['dest_dir']) && !empty($options['dest_dir'])) {
        if (!preg_match('/^\w+([\/]\w+)?$/i', $options['dest_dir'])) {
            exit('图片上传目录错误,只能指定最多两级目录,如: "WZ_store","WZ_store/d1"');
        }
    }
    $options['direct'] = true;
    $options['multiple'] = false;
    if (isset($options['thumb'])) {
        $options['thumb'] = !empty($options['thumb']);
    }
    $s = '';
    if (!defined('TPL_INIT_IMAGE')) {
        $s = '
        <script type="text/javascript">
            function showImageDialog(elm, opts, options) {
                require(["util"], function(util){
                    var btn = $(elm);
                    var ipt = btn.parent().prev();
                    var val = ipt.val();
                    var img = ipt.parent().next().children();
                    options = '.str_replace('"', '\'', json_encode($options)).';
                    util.image(val, function(url){
                        if(url.url){
                            if(img.length > 0){
                                img.get(0).src = url.url;
                            }
                            ipt.val(url.attachment);
                            ipt.attr("filename",url.filename);
                            ipt.attr("url",url.url);
                        }
                        if(url.media_id){
                            if(img.length > 0){
                                img.get(0).src = "";
                            }
                            ipt.val(url.media_id);
                        }
                    }, null, options);
                });
            }
            function deleteImage(elm){
                require(["jquery"], function($){
                    $(elm).prev().attr("src", "./resource/images/nopic.jpg");
                    $(elm).parent().prev().find("input").val("");
                });
            }
        </script>';
        define('TPL_INIT_IMAGE', true);
    }

	$s .= '
		<div class="input-group ' . $options['class_extra'] . '">
			<input type="text" name="' . $name . '" value="' . $value . '"' . ($options['extras']['text'] ? $options['extras']['text'] : '') . ' class="form-control" autocomplete="off">
			<span class="input-group-btn">
				<button class="btn btn-default" type="button" onclick="showImageDialog(this);">选择图片</button>
			</span>
		</div>
		<div class="input-group ' . $options['class_extra'] . '" style="margin-top:.5em;">
			<img src="' . $val . '" onerror="this.src=\'' . $default . '\'; this.title=\'图片未找到.\'" class="img-responsive img-thumbnail" ' . ($options['extras']['image'] ? $options['extras']['image'] : '') . ' width="150" />
			<em class="close" style="position:absolute; top: 0px; right: -14px;" title="删除这张图片" onclick="deleteImage(this)">×</em>
		</div>';
	return $s;
}


function tpl_form_field_multi_image($name, $value = array(), $options = array()) {
	global $_W;
	is_array($options) or $options = [];
	$options['multiple'] = true;
	$options['direct'] = false;
	$s = '';
	if (!defined('TPL_INIT_MULTI_IMAGE')) {
		$s = '
<script type="text/javascript">
	function uploadMultiImage(elm) {
		var name = $(elm).next().val();
		util.image( "", function(urls){
			$.each(urls, function(idx, url){
				$(elm).parent().parent().next().append(\'<div class="multi-item" style="height: auto;"><a href="\'+url.url+\'" target="_blank"><img  width="50px" height="50px"  onerror="this.src=\\\'./resource/images/nopic.jpg\\\'; this.title=\\\'图片未找到.\\\'" src="\'+url.url+\'" class="img-responsive img-thumbnail"></a><input type="hidden" name="\'+name+\'[]" value="\'+url.attachment+\'"><em class="close" title="删除这张图片" onclick="deleteMultiImage(this)">×</em></div>\');
			});
		}, "", ' . json_encode($options) . ');
	}
	function deleteMultiImage(elm){
		require(["jquery"], function($){
			$(elm).parent().remove();
		});
	}
</script>';
		define('TPL_INIT_MULTI_IMAGE', true);
	}

	$s .= <<<EOF
<div class="input-group">
	<input type="text" class="form-control" readonly="readonly" value="" placeholder="批量上传图片" autocomplete="off">
	<span class="input-group-btn">
		<button class="btn btn-default" type="button" onclick="uploadMultiImage(this);">选择图片</button>
		<input type="hidden" value="{$name}" />
	</span>
</div>
<div class="input-group multi-img-details">
EOF;
	if (is_array($value) && count($value) > 0) {
		foreach ($value as $row) {
			$s .= '
<div class="multi-item" style="height: auto;">
    <a href="' . tomedia($row) . '" target="_blank">
	<img width="50px" height="50px" src="' . tomedia($row) . '" onerror="this.src=\'./resource/images/nopic.jpg\'; this.title=\'图片未找到.\'" class="img-responsive img-thumbnail">
	</a>
	<input type="hidden" name="' . $name . '[]" value="' . $row . '" >
	<em class="close" title="删除这张图片" onclick="deleteMultiImage(this)">×</em>
</div>';
		}
	}
	$s .= '</div>';

	return $s;
}

function tpl_form_field_wechat_image($name, $value = '', $default = '', $options = array()) {
	global $_W;
	if(!$_W['acid'] || $_W['account']['level'] < 3) {
		$options['account_error'] = 1;
	} else {
		$options['acid'] = $_W['acid'];
	}
	if(empty($default)) {
		$default = './resource/images/nopic.jpg';
	}
	$val = $default;
	if (!empty($value)) {
		$media_data = (array)media2local($value, true);
		$val = $media_data['attachment'];
	}
	if (empty($options['class_extra'])) {
		$options['class_extra'] = '';
	}
	$options['direct'] = true;
	$options['multiple'] = false;
	$options['type'] = empty($options['type']) ? 'image' : $options['type'];
	$s = '';
	if (!defined('TPL_INIT_WECHAT_IMAGE')) {
		$s = '
		<script type="text/javascript">
			function showWechatImageDialog(elm, options) {
				require(["util"], function(util){
					var btn = $(elm);
					var ipt = btn.parent().prev();
					var val = ipt.val();
					var img = ipt.parent().next().children();
					util.wechat_image(val, function(url){
						if(url.media_id){
							if(img.length > 0){
								img.get(0).src = url.url;
							}
							ipt.val(url.media_id);
						}
					}, options);
				});
			}
			function deleteImage(elm){
				require(["jquery"], function($){
					$(elm).prev().attr("src", "./resource/images/nopic.jpg");
					$(elm).parent().prev().find("input").val("");
				});
			}
		</script>';
		define('TPL_INIT_WECHAT_IMAGE', true);
	}

	$s .= '
<div class="input-group ' . $options['class_extra'] . '">
	<input type="text" name="' . $name . '" value="' . $value . '"' . ($options['extras']['text'] ? $options['extras']['text'] : '') . ' class="form-control" autocomplete="off">
	<span class="input-group-btn">
		<button class="btn btn-default" type="button" onclick="showWechatImageDialog(this, ' . str_replace('"', '\'', json_encode($options)) . ');">选择图片</button>
	</span>
</div>';
	$s .=
		'<div class="input-group ' . $options['class_extra'] . '" style="margin-top:.5em;">
			<img src="' . $val . '" onerror="this.src=\'' . $default . '\'; this.title=\'图片未找到.\'" class="img-responsive img-thumbnail" ' . ($options['extras']['image'] ? $options['extras']['image'] : '') . ' width="150" />
			<em class="close" style="position:absolute; top: 0px; right: -14px;" title="删除这张图片" onclick="deleteImage(this)">×</em>
		</div>';
	if (!empty($media_data) && $media_data['model'] == 'temp' && (time() - $media_data['createtime'] > 259200)) {
		$s .= '<span class="help-block"><b class="text-danger">该素材已过期 [有效期为3天]，请及时更新素材</b></span>';
	}
	return $s;
}

function tpl_form_field_wechat_multi_image($name, $value = '', $default = '', $options = array()) {
	global $_W;
	if(!$_W['acid'] || $_W['account']['level'] < 3) {
		$options['account_error'] = 1;
	} else {
		$options['acid'] = $_W['acid'];
	}
	if(empty($default)) {
		$default = './resource/images/nopic.jpg';
	}
	if(empty($options['class_extra'])) {
		$options['class_extra'] = '';
	}

	$options['direct'] = false;
	$options['multiple'] = true;
	$options['type'] = empty($options['type']) ? 'image' : $options['type'];
	$s = '';
	if (!defined('TPL_INIT_WECHAT_MULTI_IMAGE')) {
		$s = '
<script type="text/javascript">
	function uploadWechatMultiImage(elm) {
		require(["jquery","util"], function($, util){
			var name = $(elm).next().val();
			util.wechat_image("", function(urls){
				$.each(urls, function(idx, url){
					$(elm).parent().parent().next().append(\'<div class="multi-item"><img onerror="this.src=\\\'./resource/images/nopic.jpg\\\'; this.title=\\\'图片未找到.\\\'" src="\'+url.url+\'" class="img-responsive img-thumbnail"><input type="hidden" name="\'+name+\'[]" value="\'+url.media_id+\'"><em class="close" title="删除这张图片" onclick="deleteWechatMultiImage(this)">×</em></div>\');
				});
			}, '.json_encode($options).');
		});
	}
	function deleteWechatMultiImage(elm){
		require(["jquery"], function($){
			$(elm).parent().remove();
		});
	}
</script>';
		define('TPL_INIT_WECHAT_MULTI_IMAGE', true);
	}

	$s .= <<<EOF
<div class="input-group">
	<input type="text" class="form-control" readonly="readonly" value="" placeholder="批量上传图片" autocomplete="off">
	<span class="input-group-btn">
		<button class="btn btn-default" type="button" onclick="uploadWechatMultiImage(this);">选择图片</button>
		<input type="hidden" value="{$name}" />
	</span>
</div>
<div class="input-group multi-img-details">
EOF;
	if (is_array($value) && count($value)>0) {
		foreach ($value as $row) {
			$s .='
<div class="multi-item">
	<img src="'.media2local($row).'" onerror="this.src=\'./resource/images/nopic.jpg\'; this.title=\'图片未找到.\'" class="img-responsive img-thumbnail">
	<input type="hidden" name="'.$name.'[]" value="'.$row.'" >
	<em class="close" title="删除这张图片" onclick="deleteWechatMultiImage(this)">×</em>
</div>';
		}
	}
	$s .= '</div>';
	return $s;
}

function tpl_form_field_wechat_voice($name, $value = '', $options = array()) {
	global $_W;
	if(!$_W['acid'] || $_W['account']['level'] < 3) {
		$options['account_error'] = 1;
	} else {
		$options['acid'] = $_W['acid'];
	}
	if(!empty($value)) {
		$media_data = (array)media2local($value, true);
		$val = $media_data['attachment'];
	}
	if(!is_array($options)){
		$options = array();
	}
	$options['direct'] = true;
	$options['multiple'] = false;
	$options['type'] = 'voice';

	$s = '';
	if (!defined('TPL_INIT_WECHAT_VOICE')) {
		$s = '
<script type="text/javascript">
	function showWechatVoiceDialog(elm, options) {
		require(["util"], function(util){
			var btn = $(elm);
			var ipt = btn.parent().prev();
			var val = ipt.val();
			util.wechat_audio(val, function(url){
				if(url && url.media_id && url.url){
					btn.prev().show();
					ipt.val(url.media_id);
					ipt.attr("media_id",url.media_id);
					ipt.attr("url",url.url);
					setWechatAudioPlayer();
				}
				if(url && url.media_id){
					ipt.val(url.media_id);
				}
			} , '.json_encode($options).');
		});
	}

	function setWechatAudioPlayer(){
		require(["jquery", "util", "jquery.jplayer"], function($, u){
			$(function(){
				$(".audio-player").each(function(){
					$(this).prev().find("button").eq(0).click(function(){
						var src = $(this).parent().prev().attr("url");
						if($(this).find("i").hasClass("fa-stop")) {
							$(this).parent().parent().next().jPlayer("stop");
						} else {
							if(src) {
								$(this).parent().parent().next().jPlayer("setMedia", {mp3: u.tomedia(src)}).jPlayer("play");
							}
						}
					});
				});

				$(".audio-player").jPlayer({
					playing: function() {
						$(this).prev().find("i").removeClass("fa-play").addClass("fa-stop");
					},
					pause: function (event) {
						$(this).prev().find("i").removeClass("fa-stop").addClass("fa-play");
					},
					swfPath: "resource/components/jplayer",
					supplied: "mp3"
				});
				$(".audio-player-media").each(function(){
					$(this).next().find(".audio-player-play").css("display", $(this).val() == "" ? "none" : "");
				});
			});
		});
	}

	setWechatAudioPlayer();
</script>';
		echo $s;
		define('TPL_INIT_WECHAT_VOICE', true);
	}

	$s .= '
	<div class="input-group">
		<input type="text" value="'.$value.'" name="'.$name.'" class="form-control audio-player-media" autocomplete="off" '.($options['extras']['text'] ? $options['extras']['text'] : '').'>
		<span class="input-group-btn">
			<button class="btn btn-default audio-player-play" type="button" style="display:none"><i class="fa fa-play"></i></button>
			<button class="btn btn-default" type="button" onclick="showWechatVoiceDialog(this,'.str_replace('"','\'', json_encode($options)).');">选择媒体文件</button>
		</span>
	</div>
	<div class="input-group audio-player">
	</div>';
	if(!empty($media_data) && $media_data['model'] == 'temp' && (time() - $media_data['createtime'] > 259200)){
		$s .= '<span class="help-block"><b class="text-danger">该素材已过期 [有效期为3天]，请及时更新素材</b></span>';
	}
	return $s;
}

function tpl_form_field_wechat_video($name, $value = '', $options = array()) {
	global $_W;
	if(!$_W['acid'] || $_W['account']['level'] < 3) {
		$options['account_error'] = 1;
	} else {
		$options['acid'] = $_W['acid'];
	}
	if(!empty($value)) {
		$media_data = (array)media2local($value, true);
		$val = $media_data['attachment'];
	}
	if(!is_array($options)){
		$options = array();
	}
	if(empty($options['tabs'])){
		$options['tabs'] = array('video'=>'active', 'browser'=>'');
	}
	$options = array_elements(array('tabs','global','dest_dir', 'acid', 'error'), $options);
	$options['direct'] = true;
	$options['multi'] = false;
	$options['type'] = 'video';
	$s = '';
	if (!defined('TPL_INIT_WECHAT_VIDEO')) {
		$s = '
<script type="text/javascript">
	function showWechatVideoDialog(elm, options) {
		require(["util"], function(util){
			var btn = $(elm);
			var ipt = btn.parent().prev();
			var val = ipt.val();
			util.wechat_audio(val, function(url){
				if(url && url.media_id && url.url){
					btn.prev().show();
					ipt.val(url.media_id);
					ipt.attr("media_id",url.media_id);
					ipt.attr("url",url.url);
				}
				if(url && url.media_id){
					ipt.val(url.media_id);
				}
			}, '.json_encode($options).');
		});
	}

</script>';
		echo $s;
		define('TPL_INIT_WECHAT_VIDEO', true);
	}

	$s .= '
	<div class="input-group">
		<input type="text" value="'.$value.'" name="'.$name.'" class="form-control" autocomplete="off" '.($options['extras']['text'] ? $options['extras']['text'] : '').'>
		<span class="input-group-btn">
			<button class="btn btn-default" type="button" onclick="showWechatVideoDialog(this,'.str_replace('"','\'', json_encode($options)).');">选择媒体文件</button>
		</span>
	</div>
	<div class="input-group audio-player">
	</div>';
	if(!empty($media_data) && $media_data['model'] == 'temp' && (time() - $media_data['createtime'] > 259200)){
		$s .= '<span class="help-block"><b class="text-danger">该素材已过期 [有效期为3天]，请及时更新素材</b></span>';
	}
	return $s;
}


function tpl_form_field_location_category($name, $values = array(), $del = false) {
	$html = '';
	if (!defined('TPL_INIT_LOCATION_CATEGORY')) {
		$html .= '
		<script type="text/javascript">
			require(["jquery", "location"], function($, loc){
				$(".tpl-location-container").each(function(){

					var elms = {};
					elms.cate = $(this).find(".tpl-cate")[0];
					elms.sub = $(this).find(".tpl-sub")[0];
					elms.clas = $(this).find(".tpl-clas")[0];
					var vals = {};
					vals.cate = $(elms.cate).attr("data-value");
					vals.sub = $(elms.sub).attr("data-value");
					vals.clas = $(elms.clas).attr("data-value");
					loc.render(elms, vals, {withTitle: true});
				});
			});
		</script>';
		define('TPL_INIT_LOCATION_CATEGORY', true);
	}
	if (empty($values) || !is_array($values)) {
		$values = array('cate'=>'','sub'=>'','clas'=>'');
	}
	if(empty($values['cate'])) {
		$values['cate'] = '';
	}
	if(empty($values['sub'])) {
		$values['sub'] = '';
	}
	if(empty($values['clas'])) {
		$values['clas'] = '';
	}
	$html .= '
		<div class="row row-fix tpl-location-container">
			<div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
				<select name="' . $name . '[cate]" data-value="' . $values['cate'] . '" class="form-control tpl-cate">
				</select>
			</div>
			<div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
				<select name="' . $name . '[sub]" data-value="' . $values['sub'] . '" class="form-control tpl-sub">
				</select>
			</div>
			<div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
				<select name="' . $name . '[clas]" data-value="' . $values['clas'] . '" class="form-control tpl-clas">
				</select>
			</div>';
	if($del) {
		$html .='
			<div class="col-xs-12 col-sm-3 col-md-3 col-lg-3" style="padding-top:5px">
				<a title="删除" onclick="$(this).parents(\'.tpl-location-container\').remove();return false;"><i class="fa fa-times-circle"></i></a>
			</div>
		</div>';
	} else {
		$html .= '</div>';
	}

	return $html;
}


function tpl_wappage_editor($editorparams = '', $editormodules = array()) {
	global $_GPC;
	$content = '';
	load()->func('file');
	$filetree = file_tree(IA_ROOT . '/web/themes/default/wapeditor');
	if (!empty($filetree)) {
		foreach ($filetree as $file) {
			if (strexists($file, 'widget-')) {
				$fileinfo = pathinfo($file);
				$_GPC['iseditor'] = false;
				$display = template('wapeditor/'.$fileinfo['filename'], TEMPLATE_FETCH);
				$_GPC['iseditor'] = true;
				$editor = template('wapeditor/'.$fileinfo['filename'], TEMPLATE_FETCH);
				$content .= "<script type=\"text/ng-template\" id=\"{$fileinfo['filename']}-display.html\">".str_replace(array("\r\n", "\n", "\t"), '', $display)."</script>";
				$content .= "<script type=\"text/ng-template\" id=\"{$fileinfo['filename']}-editor.html\">".str_replace(array("\r\n", "\n", "\t"), '', $editor)."</script>";
			}
		}
	}
	return $content;
}



function tpl_ueditor($id, $value = '', $options = array()) {
	$s = '';
	if (!defined('TPL_INIT_UEDITOR')) {
		$s .= '<script type="text/javascript" src="./resource/components/ueditor/ueditor.config.js"></script><script type="text/javascript" src="./resource/components/ueditor/ueditor.all.min.js"></script><script type="text/javascript" src="./resource/components/ueditor/lang/zh-cn/zh-cn.js"></script>';
	}
	$options['height'] = empty($options['height']) ? 200 : $options['height'];
	$s .= !empty($id) ? "<textarea id=\"{$id}\" name=\"{$id}\" type=\"text/plain\" style=\"height:{$options['height']}px;\">{$value}</textarea>" : '';
	$s .= "
	<script type=\"text/javascript\">
			var ueditoroption = {
				'autoClearinitialContent' : false,
				'toolbars' : [['fullscreen', 'source', 'preview', '|', 'bold', 'italic', 'underline', 'strikethrough', 'forecolor', 'backcolor', '|',
					'justifyleft', 'justifycenter', 'justifyright', '|', 'insertorderedlist', 'insertunorderedlist', 'blockquote', 'emotion', 'insertvideo',
					'link', 'removeformat', '|', 'rowspacingtop', 'rowspacingbottom', 'lineheight','indent', 'paragraph', 'fontsize', '|',
					'inserttable', 'deletetable', 'insertparagraphbeforetable', 'insertrow', 'deleterow', 'insertcol', 'deletecol',
					'mergecells', 'mergeright', 'mergedown', 'splittocells', 'splittorows', 'splittocols', '|', 'anchor', 'map', 'print', 'drafts']],
				'elementPathEnabled' : false,
				'initialFrameHeight': {$options['height']},
				'focus' : false,
				'maximumWords' : 9999999999999
			};
			var opts = {
				type :'image',
				direct : false,
				multi : true,
				tabs : {
					'upload' : 'active',
					'browser' : '',
					'crawler' : ''
				},
				path : '',
				dest_dir : '',
				global : false,
				thumb : false,
				width : 0
			};
			UE.registerUI('myinsertimage',function(editor,uiName){
				editor.registerCommand(uiName, {
					execCommand:function(){
						require(['fileUploader'], function(uploader){
							uploader.show(function(imgs){
								if (imgs.length == 0) {
									return;
								} else if (imgs.length == 1) {
									editor.execCommand('insertimage', {
										'src' : imgs[0]['url'],
										'_src' : imgs[0]['attachment'],
										'width' : '100%',
										'alt' : imgs[0].filename
									});
								} else {
									var imglist = [];
									for (i in imgs) {
										imglist.push({
											'src' : imgs[i]['url'],
											'_src' : imgs[i]['attachment'],
											'width' : '100%',
											'alt' : imgs[i].filename
										});
									}
									editor.execCommand('insertimage', imglist);
								}
							}, opts);
						});
					}
				});
				var btn = new UE.ui.Button({
					name: '插入图片',
					title: '插入图片',
					cssRules :'background-position: -726px -77px',
					onclick:function () {
						editor.execCommand(uiName);
					}
				});
				editor.addListener('selectionchange', function () {
					var state = editor.queryCommandState(uiName);
					if (state == -1) {
						btn.setDisabled(true);
						btn.setChecked(false);
					} else {
						btn.setDisabled(false);
						btn.setChecked(state);
					}
				});
				return btn;
			}, 19);
			".(!empty($id) ? "
				$(function(){
					var ue = UE.getEditor('{$id}', ueditoroption);
					$('#{$id}').data('editor', ue);
					$('#{$id}').parents('form').submit(function() {
						if (ue.queryCommandState('source')) {
							ue.execCommand('source');
						}
					});
				});" : '')."
	</script>";
	return $s;
}


function tpl_edit_sms($name, $value, $uniacid, $url, $num) {
	$s = '
				<div class="input-group">
					<input type="text" name="'.$name.'" id="balance" readonly value="'.$value.'" class="form-control" autocomplete="off">
					<span class="input-group-btn">
						<button type="button" class="btn btn-default" data-toggle="modal" data-target="#edit_sms">编辑短信条数</button>
					</span>
				</div>
				<span class="help-block">请填写短信剩余条数,必须为整数。</span>

		<div class="modal fade" id="edit_sms" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
						<h4 class="modal-title" id="">修改短信条数</h4>
					</div>
					<div class="modal-body" style="height: 100px;">
						<div class="form-group">
							<label class="col-xs-12 col-sm-5 col-md-6 col-lg-3 control-label">短信条数</label>
							<div class="col-sm-6 col-xs-12 col-md-7">
								<div class="input-group" style="width: 180px;">
									<div class="input-group-btn">
										<button type="button" class="btn btn-defaultt label-success" id="edit_add">+</button>
									</div>
									<!--<span class="input-group-addon label-danger"  id="edit_alert" style="width: 10px;">+ </span>-->
									<input type="text" class="form-control" id="edit_num" value="+">
									<div class="input-group-btn">
										<button type="button" class="btn btn-default" id="edit_minus">-</button>
									</div>
								</div>

								<div class="help-block">点击加号或减号切换修改短信条数方式<br>最多可添加<span id="count_sms">'.$num.'</span>条短信</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" id="edit_sms_sub" class="btn btn-primary">保存</button>
					</div>
				</div>
			</div>
		</div>
		<script>
		var status = \'add\';
			$(\'#edit_add\').click(function() {
				status = \'add\';
				var sign = status == \'add\' ? \'+\' : \'-\';
				var edit_num = $(\'#edit_num\').val();
				if (edit_num == \'\') {
					$(\'#edit_num\').val(sign)
					return;
				}
				if (isNaN(edit_num.substr(1)) || edit_num.substr(1) == \'\') {
					edit_num = \'\';
				}
				$(\'#edit_num\').val(\'+\'+Math.abs(edit_num));
				if (edit_num == \'\') {
					$(\'#edit_num\').val(sign);
				}
				$(\'#edit_add\').attr(\'class\', \'btn btn-defaultt label-success\');
				$(\'#edit_minus\').attr(\'class\', \'btn btn-default\');
			});
			$(\'#edit_num\').keyup(function() {
				var sign = status == \'add\' ? \'+\' : \'-\';
				if ($(\'#edit_num\').val() == \'\') {
					return ;
				}
				if (isNaN($(\'#edit_num\').val()) && $(\'#edit_num\').val() != sign) {
					$(\'#edit_num\').val(\'\');
					return;
				}
				if ($(\'#edit_num\').val().indexOf(sign) < 0) {
				var val = parseInt(Math.abs($(\'#edit_num\').val()));
				if (val == 0) {
					$(\'#edit_num\').val(sign);
				} else {
					$(\'#edit_num\').val(sign + val);
				}
				}

			});
			$(\'#edit_minus\').click(function() {
				status = \'minus\';
				var sign = status == \'add\' ? \'+\' : \'-\';
				var edit_num = $(\'#edit_num\').val();
				if (edit_num == \'\') {
					$(\'#edit_num\').val(sign)
					return;
				}
				if (isNaN(edit_num.substr(1)) || edit_num.substr(1) == \'\') {
					edit_num = \'\';
				}
				$(\'#edit_num\').val(\'-\'+Math.abs(edit_num));
				if (edit_num == \'\') {
					$(\'#edit_num\').val(sign);
				}
				$(\'#edit_minus\').attr(\'class\', \'btn btn-defaultt label-danger\');
				$(\'#edit_add\').attr(\'class\', \'btn btn-default\');
			});
			$(\'#edit_sms_sub\').click(function () {
				var edit_num = $(\'#edit_num\').val() == \'\' ? 0 : Math.abs(parseInt($(\'#edit_num\').val()));
				var uniacid = '.$uniacid.';
				$.post(\''.$url.'\', {\'balance\' : edit_num, \'uniacid\' : uniacid, \'status\' : status}, function(data) {
					var data = $.parseJSON(data);

					$(\'#count_sms\').html(data.message.message.count);
					if (data.message.errno > 0) {
						$(\'#balance\').val(data.message.message.num);
						$(\'#edit_sms\').modal(\'toggle\');
					} else {
						util.message(\'您现有短信数量为0，请联系服务商购买短信\');
						$(\'#edit_sms\').modal(\'toggle\');
					}
					$(\'#edit_num\').val(\'\');
					$(\'#edit_add\').trigger(\'click\');
				});
			});
			</script>
	';
	return $s;
}


function tpl_form_common_select($name,$rule,$val = '',$where_rule = '') {
    global $common_select_conf;
	global $common_select_join_conf;
	global $common_select_add_field_conf;
    $rules = explode("|", $common_select_conf[$rule]);
	$where = $rules['1']." = '{$val}' ";
	$rules_array =array($rules['1'],$rules['2']);
	$un_left_join='';
	$field_name='';

	if(!empty($common_select_join_conf[$rule])){
		$rules_join = explode("|", $common_select_join_conf[$rule]);
		if($common_select_add_field_conf[$rule]){
			$rules_add_field = explode("|", $common_select_add_field_conf[$rule]);
            array_unshift($rules_add_field,$rules['1'],$rules['2']);
            $rules_array=$rules_add_field;
		}
		$field_name=$rules_join['0'];
		$un_left_join=$rules_join['1'];
	}

    /* if($rules['0']!='dt_pub_flowtype') {        //这个特殊处理
        $where .= select_rule_where('', '', $rules['0']);
    } */

    if (!empty($rules[3])) {
        $where .= ' AND '.$rules[3];
    }
    $data = pdo_get($rules['0'], $where,$rules_array,$un_left_join,$field_name);

    $where_str = '';
    if ($where_rule) {
        if (!strpos($where_rule, ';')) {
            $where_rule .= ':'.$where_rule;
        }

        list($elm_name,$where_field_name) = explode(':', $where_rule);
        $where_str = <<<EOT
add_where = "{$where_field_name}="+\$('[name="{$elm_name}"]').val();
EOT;

    }
    $s = '';
    //if (!defined('TPL_INIT_SELECT_LINK')) {
        $s = '
        <script type="text/javascript">
            function '.$name.'_showCommonSelects(elm, page) {
                require(["util","jquery"], function(u, $){
                
                    var add_where = "";
                    '.$where_str.'
                    u.showCommonSelect(function(href, page){
                        if (page != "" && page != undefined) {
                            u.showCommonSelect(arguments.callee, page,"'.$rule.'", href);
                            return false;
                        }
                        var idname = href.split(":");
                        var ipts = $(elm).parent().prev();
                        ipts.val(href);
                        ipts.prev().val(idname[1]);
                        
                        
                        $_this = ipts.prev().prev();
                        $_this.val(idname[0]);
                        $_this.change();
                        //console.log($_this, idname[0]);
                    }, page,"'.$rule.'", "", add_where);
                });
            }               
        </script>';
        //define('TPL_INIT_SELECT_LINK', true);
    //}

    $s .= '
    <div class="input-group">
        <input type="text" class="form-control" id="tpl_common_select_'.$name.'" name="'.$name.'" value="'.$val.'" style="display:none;" >
        <input type="text" class="form-control" name="'.$name.'_value" value="'.$data[$rules['2']].'" style="display:none;" >
        <input type="text" class="form-control" name="'.$name.'_show"  readonly="readonly" value="'.$data[$rules['1']].":".$data[$rules['2']].'">
        <span class="input-group-btn">
            <a href="javascript:" class="btn btn-default" onclick="'.$name.'_showCommonSelects(this)">请选择</a>
        </span>
    </div>
    ';
    return $s;
}


//模块列表
function tpl_form_modules_select($name,$rule,$val) {
    global $common_select_conf;
    $rules = explode("|", $common_select_conf[$rule], 4);
    $where = $rules['1']." = '{$val}' ";
    if($rules['0']!='dt_pub_flowtype') {        //这个特殊处理
        $where .= select_rule_where('', '', $rules['0']);
    }
	
    if (!empty($rules[3])) {
        $where .= ' AND '.$rules[3];
    }
	
    $data = pdo_get($rules['0'], $where, array($rules['1'],$rules['2']));
	//var_dump($data);
	//exit();
    $s = '';
    //if (!defined('TPL_INIT_SELECT_LINK')) {
        $s = '
        <script type="text/javascript">
            function '.$name.'_showModulesSelects(elm, page) {
                require(["util","jquery"], function(u, $){
                    u.showModulesSelect(function(href, page, module){
                        if (page != "" && page != undefined) {
                            u.showModulesSelect(arguments.callee, page,"'.$rule.'", href, module);
                            return false;
                        }
                        var idname = href.split(":");
                        var ipts = $(elm).parent().prev();
                        ipts.val(href);
                        ipts.prev().val(idname[1]);
                        ipts.prev().prev().val(idname[0]);
                    }, page,"'.$rule.'");
                });
            }               
        </script>';
        //define('TPL_INIT_SELECT_LINK', true);
    //}
    $s .= '
    <div class="input-group">
        <input type="text" class="form-control" idgroup="'.$name.'[]" value="'.$val.'" style="display:none;" >
        <input type="text" class="form-control" eid_valuegroup="'.$name.'_value[]" value="'.$data[$rules['2']].'" style="display:none;" >
        <input type="text" class="form-control" eid_showgroup="'.$name.'_show[]"  readonly="readonly" value="'.$data[$rules['1']].":".$data[$rules['2']].'">
        <span class="input-group-btn">
            <a href="javascript:" class="btn btn-default" onclick="'.$name.'_showModulesSelects(this)">请选择</a>
        </span>
    </div>
    ';
    return $s;
}


function tpl_form_operating_tab($rule,$do,$isedit,$add_show=1,$menu_titel=''){
    global $_W;
    $account_type = $_W['user']['idtype'];
    $account_str = $_W['user']['idstr'];
    $shows = explode("," , $add_show);
    $menu_permission = str_replace('/', '_',$rule);
    $menu = pdo_fetch('SELECT id,pid,permission_name,type,title,url FROM ' . tablename('core_menu') . ' WHERE  is_display = 1 AND idstr="'.$account_str.'"  and permission_name=\''.$menu_permission.'\'' );
    $html = '<ul class="nav nav-tabs">';
    if(in_array($account_type, $shows)||$add_show==1){
		$menu_titel = $menu_titel ? $menu_titel : $menu['title'];
        $menu_url = $menu['url'] ? $menu['url'] : url($rule);
        $html .= ($do=='post') ? '<li ><a href="'.$menu_url.'">返回</a></li>' : '';
        $html .= '
            <li '.(($do=='post') ? "class=active" : "").'><a href="'.url($rule.'/post').'">'.(($isedit) ? " 编辑": "添加").$menu_titel.'</a></li>
            ';
    }
//  $url_rule = str_replace('dt_','dt/',$menu['permission_name']);
//  $active = (($do=='display')&&($url_rule==$rule)) ? "class=active" : "";
//  $html .= '<li '.$active.'><a href="'.$menu['url'].'">'.$menu['title'].'</a></li>';
    
    if ($menu){
        $pid = ($menu['type']=='url')? $menu['id'] : $menu['pid'];
        $arr_menu = pdo_fetchall('SELECT id,name,pid,url,type, title, append_title,permission_name FROM ' . tablename('core_menu') . ' WHERE  is_display = 1 and pid=\''.$pid.'\' ORDER BY displayorder DESC');
        foreach ($arr_menu as $_menu){
            if ($_menu['type']=='permission'){
                $url_rule = str_replace('dt_','dt/',$_menu['permission_name']);
                $active = (($do=='display')&&($url_rule==$rule)) ? "class=active" : "";
                $html .= '<li '.$active.'><a href="'.$_menu['url'].'">'.$_menu['title'].'</a></li>';
                
            }
        }
    }
    
    $html .= '</ul>';
    return $html;
}

function tpl_form_field_bank_org($values = array(),$list=true,$disabled=false,$class_number='4') {
    global $_W;
    
    $account_type = $_W['user']['idtype'];
    $html = '';
	
    if($account_type == 'SYS'){
		if (empty($values) || !is_array($values)) {
            $values = array('orgcode'=>'','netcode'=>'');
        }
        if(empty($values['orgcode'])) {
            $values['orgcode'] = '';
        }
        if(empty($values['netcode'])) {
            $values['netcode'] = '';
        }

        if($disabled){
            $disableds = ' disabled="disabled" ';
        }
		
		$option = '';
		$orglist = orglist();
		if($orglist){
			foreach ($orglist as $k=>$v) {
				$selected = '';
				if($k == $values['orgcode']){
					$selected = 'selected';
					
				}else{
					$selected = '';
				}
				$option .= "<option value='{$k}' {$selected}>{$v}</option>";
			}
			$option_title = $list ? '全部' : '请选择';
			$html .= '<div class="form-group">
					<label class="col-xs-12 col-sm-2 col-md-2 control-label">所属机构</label>
					<div class="col-sm-'.$class_number.' col-xs-12">
						<select class="form-control" style="margin-right:15px;" name="orgcode" '.$disableds.'>
							<option value="0">'.$option_title.'</option>
							'.$option.'
						</select>
					</div>
					<label class="col-xs-12 col-sm-1 col-md-1 control-label">所属网点</label>
                    <div class="col-sm-'.$class_number.' col-xs-12">
                        <select class="form-control" style="margin-right:15px;" name="netcode" '.$disableds.'>
                            <option value="0">'.$option_title.'</option>
                        </select>
                    </div>
					
				</div>
				<script>
				$("select[name=orgcode]").change(function(){
					var value = $(this).val();
					var url = "./index.php?c=utility&a=link&do=netlist";
					
					$.get(url, {"orgcode": value,"netcode":"'.$values['netcode'].'","option_title":"'.$option_title.'"}, function(result, status){
						$("select[name=netcode]").html(result);
					});
				}).change();
				</script>';
        
		}  
                
    }

    return $html;
}
function tpl_form_field_bank_net($values = array(),$list=true,$disabled=false,$class_number='4') {
    global $_W;

    $account_type = $_W['user']['idtype'];
    $html = '';

    if($account_type){
		if (empty($values) || !is_array($values)) {
            $values = array('orgcode'=>'','netcode'=>'');
        }
        if(empty($values['orgcode'])) {
            $values['orgcode'] = '';
        }
        if(empty($values['netcode'])) {
            $values['netcode'] = '';
        }

        if($disabled){
            $disableds = ' disabled="disabled" ';
        }
		$option = '';
        $orglist = orglist();
        if($account_type!='SYS'){
            $orglist_name = $orglist[$_W['user']['orgcode']];
            $orglist = array($_W['user']['orgcode'] =>$orglist_name);
        }
		if($orglist){
			foreach ($orglist as $k=>$v) {
				$selected = '';
				if($k == $values['orgcode']){
					$selected = 'selected';

				}else{
					$selected = '';
				}
				$option .= "<option value='{$k}' {$selected}>{$v}</option>";
			}
			$option_title = $list ? '全部' : '请选择';
			$html .= '<div class="form-group">
					<label class="col-xs-12 col-sm-2 col-md-2 control-label">所属机构</label>
					<div class="col-sm-'.$class_number.' col-xs-12">
						<select class="form-control" style="margin-right:15px;" name="orgcode" '.$disableds.'>
							<option value="0">'.$option_title.'</option>
							'.$option.'
						</select>
					</div>
					<label class="col-xs-12 col-sm-1 col-md-1 control-label">所属网点</label>
					<div class="col-sm-'.$class_number.' col-xs-12">
						<select class="form-control" style="margin-right:15px;" name="netcode" '.$disableds.'>
							<option value="0">'.$option_title.'</option>
						</select>
					</div>
				</div>
				<script>
				$("select[name=orgcode]").change(function(){
					var value = $(this).val();
					var url = "./index.php?c=utility&a=link&do=netlist";
					
					$.get(url, {"orgcode": value,"netcode":"'.$values['netcode'].'","option_title":"'.$option_title.'"}, function(result, status){
						$("select[name=netcode]").html(result);
					});
				}).change();
				</script>';

		}

    }

    return $html;
}

//拓展
function tpl_form_field_sale($values = array(), $del = false) {
    global $_W;
	$account_type = $_W['user']['idtype'];

    $html = '';
    if($account_type == 'SYS') {

        $html .= '<div class="form-group">
                    <label class="col-xs-12 col-sm-2 col-md-2 control-label">拓展单位类别</label>
                    <div class="col-sm-8 col-md-8 col-lg-8 col-xs-12">';


        if (empty($values) || !is_array($values)) {
            $values = array('sale_idtype' => '', 'sale_unitcode' => '', 'sale_person' => '');
        }
        if (empty($values['sale_idtype'])) {
            $values['sale_idtype'] = '';
        }
        if (empty($values['sale_unitcode'])) {
            $values['sale_unitcode'] = '';
        }
        if (empty($values['sale_person'])) {
            $values['sale_person'] = '';
        }
        $html .= '

                <div class="row row-fix tpl-location-container">
                    <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                        <select name="sale_idtype" data-value="' . $values['sale_idtype'] . '" class="form-control tpl-sale_idtype" id="sale_idtype">
                            <option value="">全部</option>
                            <option value="ORG"  {if $item["sale_idtype"] =="ORG"} selected{/if}>银行</option>
                            <option value="NET" {if $item["sale_idtype"] =="NET"} selected{/if}>网点</option>
                            <option value="SALE" {if $item["sale_idtype"] =="SALE"} selected{/if}>拓展商户</option>
                        </select>
                    </div>
                    <label class="col-xs-3 col-sm-1 col-md-1 control-label tpl-sale_unitcode">拓展单位</label>
                    <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                        <select name="sale_unitcode" data-value="' . $values['sale_unitcode'] . '" class="form-control tpl-sale_unitcode" id="sale_unitcode">
                            <option value="">全部</option>
                        </select>
                    </div>
                    <label class="col-xs-3 col-sm-1 col-md-1 control-label tpl-sale_person">拓展人</label>
                    <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                        <select name="sale_person" data-value="' . $values['sale_person'] . '" class="form-control tpl-sale_person" id="sale_person">
                        <option value="">全部</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>';


        if ($del) {
            $html .= '
                <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3" style="padding-top:5px">
                    <a title="删除" onclick="$(this).parents(\'.tpl-location-container\').remove();return false;"><i class="fa fa-times-circle"></i></a>
                </div>
            </div>';
        } else {
            $html .= '</div>';
        }

    }else{
        $html .= '<div class="form-group">
                    <label class="col-xs-12 col-sm-2 col-md-2 control-label">拓展单位类别</label>
                    <div class="col-sm-8 col-md-8 col-lg-8 col-xs-12">';

        if (empty($values) || !is_array($values)) {
            $values = array('sale_idtype' => '', 'sale_unitcode' => '', 'sale_person' => '');
        }
        if (empty($values['sale_idtype'])) {
            $values['sale_idtype'] = '';
        }
        if (empty($values['sale_unitcode'])) {
            $values['sale_unitcode'] = '';
        }
        if (empty($values['sale_person'])) {
            $values['sale_person'] = '';
        }
        $html .= '
                <div class="row row-fix tpl-location-container">
                    <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">
                        <select name="sale_idtype" data-value="' . $values['sale_idtype'] . '" class="form-control tpl-sale_idtype" id="sale_idtype">
                            <option value="">全部</option>
                            <option value="NET" {if $item["sale_idtype"] =="NET"} selected{/if}>网点</option>
                            <option value="SALE" {if $item["sale_idtype"] =="SALE"} selected{/if}>拓展商户</option>
                        </select>
                    </div>
                    <label class="col-xs-3 col-sm-1 col-md-1 control-label tpl-sale_unitcode">拓展单位</label>
                    <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 tpl-sale_unitcode">
                        <select name="sale_unitcode" data-value="' . $values['sale_unitcode'] . '" class="form-control tpl-sale_unitcode" id="sale_unitcode">
                            <option value="">全部</option>
                        </select>
                    </div>
                    <label class="col-xs-3 col-sm-1 col-md-1 control-label tpl-sale_person">拓展人</label>
                    <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3 tpl-sale_person">
                        <select name="sale_person" data-value="' . $values['sale_person'] . '" class="form-control tpl-sale_person" id="sale_person">
                        <option value="">全部</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>';
        if ($del) {
            $html .= '
                <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3" style="padding-top:5px">
                    <a title="删除" onclick="$(this).parents(\'.tpl-location-container\').remove();return false;"><i class="fa fa-times-circle"></i></a>
                </div>
            </div>';
        } else {
            $html .= '</div>';
        }

    }

    return $html;
}

function tpl_form_search_button($rule = ''){
    //global $_W;
    $menu_permission = str_replace('/','_',$rule);
    $config = [];
    $config['dt_total_statistics'] = true;
    $config['dt_total_coupon'] = true;
    $config['dt_member_coupon'] =true;
    $config['dt_total_voucher'] =true;
    $config['dt_member_voucher']= true;
    $config['dt_order_merchant'] =true;
    $config['dt_total_biz_order'] =true;
    $config['dt_dp_order'] =true;
    $config['dt_logistics_order']= true;
    $config['dt_total_logistics_clearing']= true;
    $config['dt_order']= true;
    $config['dt_order_goods']= true;
    $config['dt_goods_seckill']= true;
    $config['dt_goods']= true;
    $config['dt_taxi_team']= true;
    $config['dt_taxi_cart']= true;
    $config['dt_taxi_driver']= true;
    $config['dt_taxi_relate']= true;
    $config['dt_card_member']= true;
    $config['dt_order_add_onebuy']= true;
    $config['dt_users']= true;
//统计
    $config['dt_repday_user']= true;
    $config['dt_repday_netcode']= true;
    $config['dt_repday_meruser']= true;
    $config['dt_repday_pay']= true;
    $config['dt_repday_mer']= true;
//提现
    $config['dt_ticket_withdraw']= true;

    $config['dt_cls_merday']= true;
    $config['dt_bill_mercollect']= true;
    $config['dt_bill_merpay']= true;
//优惠券管理
    $config['dt_cls_coupon_used']= true;
    $config['dt_cls_coupon_issure']= true;
    $config['dt_cls_coupon_mer']= true;

//收银通
    $config['dt_syt_bustrade'] = true;
    $config['dt_syt_busrefund'] = true;
    $config['dt_syt_grotrade'] = true;
    $config['dt_syt_grorefund'] = true;
    $config['dt_syt_busdl'] = true;
//收银员统计
    $config['dt_repday_cashier']=true;

    $html = '<button class="btn btn-primary" name="select" value="select"><i class="fa fa-search"></i> 搜索</button>';
    !empty($config[$menu_permission]) and $html .=' <button name="export" value="export" class="btn btn-default"><i class="fa fa-download"></i> 导出数据</button>';
    return $html;

}

function tpl_form_operating_button($rule,$id,$id_field,$item=array()){
    global $_W;

    $menu_permission = str_replace('/', '_', $rule);
    $html = '';
    
	$where = ' WHERE 1 ';
	
	$account_type = $_W['user']['idtype'];
	$where .= ' AND is_display = 1  and menu_permission=\''.$menu_permission.'\'';
    
	
	isAdminUser() AND $where .= " AND FIND_IN_SET('{$account_type}',permission_dispaly)";
    
    $action = pdo_fetchall('SELECT * FROM ' . tablename('dt_sys_action') . $where);
    foreach ($action as $val) {
        $onclick = ($val['action_onclick']!='') ? 'onclick="'.htmlspecialchars_decode($val['action_onclick']).'"' : '';
        $url_rule = str_replace('dt_', 'dt/', $val['menu_permission']);
        $action = (strstr($val['action_val'], 'dt/')) ? $val['action_val']:$url_rule.'/'.$val['action_val'];
        $param = array();
        if ($val['url_parameter']){
            $url_parameter = explode('&', $val['url_parameter']);
            foreach ($url_parameter as $_val){
                $par = explode('=', $_val);
                $param[$par[0]] = $item[$par['1']];
            }
        }
        $param[$id_field] = $id;
        if ($val['action_val']=='post'){$param['edit']=1;}
        if ($val['action_type']=='ajax'){
            $html .= '<a '.$onclick.' href="javascript:;" id="'.$val['action_val'].'" data-id="'.$id.'">'.$val['action_cn'].'</a>&nbsp;&nbsp;';
        }elseif ($val['action_type']=='url'){
            $html .= '<a '.$onclick.' href="'.url($action, $param).'" >'.$val['action_cn'].'</a>&nbsp;&nbsp;';
        }

        
    }
    return $html;
    
}


function tpl_form_pub_option($name, $values, $tbname, $dvalue = '全部') {
    $param = "tblname='{$tbname}' AND fldname='{$name}'";
    $options = pdo_fetchall("SELECT tblname,fldname,memcode,value_char FROM ".tablename('dt_pub_option')." WHERE ".$param);
    $html = '';
    $html .= '
        <div class="row row-fix">
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">';
    $html .='<select name="'.$name.'" data-value="' . $values . '" class="form-control tpl-'.$name.'"> ';
    $optionstr = '';
    $has_selected = false;
    foreach($options as $opt){
        if($values==$opt['memcode']){
            $optionstr .='<option selected value="'.$opt['memcode'].'">'.$opt['value_char'].'</option>';
            $has_selected = true;
        }else{
            $optionstr .='<option value="'.$opt['memcode'].'">'.$opt['value_char'].'</option>';
        }
    }

    if ($has_selected) {
        $html .= '<option value="" selected>'.$dvalue.'</option>';
    } else {
        $html .= '<option value="">'.$dvalue.'</option>';
    }
    $html .= $optionstr;

    $html .=' </select></div>';

    $html .= '</div>';


    return $html;
}

function tpl_form_field_code($name, $values = array(), $del = false) {

    $arr_menu = pdo_fetchall("select transcode,name from dt_pub_transcode");
    $html = '';
    $html .= '
            <div class="col-xs-12 col-sm-4">';
    $html .='<select name="'.$name.'" data-value="' . $values['orgcode'] . '" class="form-control tpl-orgcode"> ';
    $html .='<option selected value="">全部</option>';
    foreach($arr_menu as $trans){
        if($values['transcode']==$trans['transcode']){
            $html .='<option selected value="'.$trans['transcode'].'">'.$trans['name'].'</option>';
        }else{
            $html .='<option value="'.$trans['transcode'].'">'.$trans['name'].'</option>';
        }
    }
    $html .=' </select></div>';


    return $html;
}

function tpl_form_field_termtype($name, $values = array(), $del = false){
    $arr_menu = pdo_fetchall("select termtype,memo from dt_term_type");
    $html = '';
    $html .= '
            <div class="col-xs-12 col-sm-4">';
    $html .='<select name="'.$name.'" data-value="' . $values['orgcode'] . '" class="form-control tpl-orgcode"> ';
    $html .='<option selected value="">全部</option>';
    foreach($arr_menu as $trans){
        if($values['termtype']==$trans['termtype']){
            $html .='<option selected value="'.$trans['termtype'].'">'.$trans['memo'].'</option>';
        }else{
            $html .='<option value="'.$trans['termtype'].'">'.$trans['memo'].'</option>';
        }
    }
    $html .=' </select></div>';


    return $html;
}



function tpl_form_dep($name, $values = array(),$tablename ='',$key_name='') {

    $arr_menu = pdo_fetchall("select depcode,depname from dt_org_dep");
    $html = '';
    $html .= '
        <div class="row row-fix">
            <div class="col-xs-12 col-sm-3 col-md-3 col-lg-3">';
    $html .='<select name="'.$name.'" data-value="' . $values['node_code'] . '" class="form-control tpl-orgcode"> ';
    $html .='<option selected value="">全部</option>';
    foreach($arr_menu as $trans){
        if($values['node_code']==$trans['depcode']){
            $html .='<option selected value="'.$trans['depcode'].'">'.$trans['depname'].'</option>';
        }else{
            $html .='<option value="'.$trans['depcode'].'">'.$trans['depname'].'</option>';
        }

    }

    $html .=' </select></div>';

    $html .= '</div>';


    return $html;
}

function tpl_form_field_taxi_team($team_no, $field = 'team_no') {
    global $_GPC;
    $team_no or $team_no = $_GPC['team_no'];

    $team_list = pdo_fetchall("SELECT `no`, `name` FROM dt_taxi_team");

    $html = '<select name="'.$field.'" class="form-control tpl-orgcode">
                <option value="">全部</option>';

    foreach ($team_list as $v) {
        if ($team_no == $v['no']) {
            $html .= '<option value="'.$v['no'].'" selected>'.$v['name'].'</option>';
        } else {
            $html .= '<option value="'.$v['no'].'">'.$v['name'].'</option>';
        }
    }

    $html .= '</select>';

    return $html;
}
function tpl_form_flow_nodetype($flow_nodetype, $field = 'flow_nodetype') {
    global $_GPC;
    $flow_nodetype or $flow_nodetype = $_GPC['flow_nodetype'];

    $team_list = pdo_fetchall("SELECT `flow_class`, `flowname` FROM dt_pub_flowtype");

    $html = '<select name="'.$field.'" class="form-control tpl-orgcode">
                <option value="">全部</option>';

    foreach ($team_list as $v) {
        if ($team_no == $v['no']) {
            $html .= '<option value="'.$v['no'].'" selected>'.$v['name'].'</option>';
        } else {
            $html .= '<option value="'.$v['no'].'">'.$v['name'].'</option>';
        }
    }

    $html .= '</select>';

    return $html;
}

function tplShowStaticActionsTabs($controller, $action, $do, $current_do_cn = '')
{
    $permission = $controller.'_'.$action;

    $rows = pdo_getall('dt_sys_action', array(
        'menu_permission'=>$permission,
        'view_mode'=>$do,
        'is_static'=>1,
    ), array('action_cn','action_val'));

    $ul_tpl = '';

    $ret_li = "<li><a href=\"javascript:history.go(-1);\">返回</a></li>";

    $do == 'display' or $ul_tpl .= $ret_li;

    $current_do_cn and $ul_tpl .= "<li class=\"active\"><a href=\"javascript:;\">{$current_do_cn}</a></li>";

    if ($rows) {
        foreach ($rows as $row) {
            $ul_tpl .= "<li><a href=\"index.php?c={$controller}&a={$action}&do={$row['action_val']}\">{$row['action_cn']}</a></li>";
        }
    }
    $ul_tpl and $ul_tpl = "<ul class=\"nav nav-tabs\">{$ul_tpl}</ul>";
    return $ul_tpl;
}

/**
 * 添加一条text文本
 */
function tpl_form_field_more($field, $values) {
    // $t组装添加的html代码
    $t = "<div class='input-group'><input type='text' class='form-control' autocomplete='off' name='".$field."[]' value=''>";
    $t .= "<span class='input-group-btn'><button class='btn btn-default' type='button' onclick='tpl_form_field_more_del_".$field."(this);'>删除本条</button></span></div>";

    // js代码
    $s = '<script type="text/javascript">
            function tpl_form_field_more_add_'.$field.'() {
                $(".btn_'.$field.'").before("'.$t.'");
            }
            function tpl_form_field_more_del_'.$field.'(obj) {
                $(obj).parent().parent().remove();
            }               
        </script>';

    if (is_array($values)) {
        //每一条结果都列出来
        foreach ($values as $key => $value) {
            if ($key != 0) {
                $s .= '<div class="input-group">';
            }
            $s .= '<input type="text" class="form-control" autocomplete="off" name="'.$field.'[]" value="'.$value.'">';

            if ($key != 0) {
                $s .='<span class="input-group-btn"><button class="btn btn-default" type="button" onclick="tpl_form_field_more_del_'.$field.'(this);">删除本条</button></span></div>';
            }
        }
    } else {
        $s .= '<input type="text" class="form-control" autocomplete="off" name="'.$field.'[]">';
    }


    //添加一条的按钮
    $s .= '<span class="input-group-btn btn_'.$field.'">
            <button class="btn btn-default" type="button" onclick="tpl_form_field_more_add_'.$field.'()">添加一条</button>
        </span>';

    return $s;
}

function tpl_show_info($list, $val)
{
    $info = isset($list[$val])?$list[$val]:$list['default'];
    return sprintf('<span class="label label-%s">%s</span>', $info['class'], $info['text']);
}

function tpl_show_option($options, $val, $dval = '')
{
    if (!empty($options[$val])) {
        return $options[$val];
    }

    return $dval;
}

/**
 * 匹配内容高亮显示
 * $name 原内容
 * $getname 匹配内容
 */
function tpl_high_light($name, $getname){
	if($getname) {
		$s = preg_replace("/($getname)/i","<span style=\"color:red\">\\1</span>",$name);
	} else {
		$s = $name;
	}
	return $s;
}

function bankNetOption($netcode,$select=false){
	global $_W;
	$bankNetOption = '';
	if($_W['user']['idtype'] == 'NET'){
		$idstr = $_W['user']['idstr'];
		
		$selected = '';
		if($idstr  == $netcode ){
			$selected = 'selected';
		} 
		$net_item = pdo_get('dt_bank_net',['netcode'=>$idstr],'netcode,abbname,parentcode,nettype');
		
		
		$select	AND $bankNetOption .= '<option value="" >全部</option>';	
		$bankNetOption .= '<option value="'.$net_item['netcode'].'" '.$selected.'>'.$net_item['abbname'].'</option>';

		if( $net_item['nettype'] == 'BRANCH') {
			$bankNetList = CT('net')->where(['parentcode'=>$idstr])->getPairs('netcode','abbname');
			if($bankNetList){
				$selected = '';
				foreach($bankNetList as $key=>$net){
					if($key == $netcode){
						$selected = 'selected';
					}else{
						$selected = '';
					}
					$bankNetOption .= '<option value="'.$key.'" '.$selected.'>----'.$net.'</option>';
				}
			}
		
		}
	}
	return $bankNetOption;
}



//机构搜索下拉选项 xlan20171025
function tpl_form_field_org($values = [],$name='',$list=true,$disabled=false) {
    global $_W;
    $html = '';
    //默认选择机构
    if (empty($values) || !is_array($values)) {
        $values = array('orgcode'=>'');
    }
    if(empty($values['orgcode'])) {
        $values['orgcode'] = '';
    }
    if($disabled){
        $disableds = ' disabled="disabled" ';
    }
    //字段名
    if($name){
        $name = ' name="'.$name.'" ';
    }else{
        $name = ' name="orgcode" ';
    }


    $option = '';
    load()->classs('role');
    $role = Role::loadRole($_W['user']?:null);

    $orglist = $role->getOrgList();
    $orgoptions = array_column($orglist, 'name', 'orgcode');

    if($orgoptions){
        foreach ($orgoptions as $k=>$v) {
            $selected = '';
            if($k == $values['orgcode']){
                $selected = 'selected';
            }else{
                $selected = '';
            }
            $option .= "<option value='{$k}' {$selected}>{$v}</option>";
        }
        $option_title = $list ? '全部' : '请选择';
        $html .= '<select class="form-control" style="margin-right:15px;" '.$name.$disableds.'>
							<option value="">'.$option_title.'</option>'.$option.'</select>';
    }

    return $html;
}

//终端名称
function termtype_name(){
    $term_name=array(
        'O2O'=>'O2O',
        'MCP'=>'收银台',
        'DDSHOW'=>'订点秀屏',
        'POS'=>'POS机',
        'MAPP'=>'商户APP',
        'WEB'=>'微信总门户',
        'WEBORG'=>'机构门户',
        'WEBNET'=>'网点空间',
        'WEBMER'=>'商户主页',

    );
    return $term_name;
}
//广告位置
function termtype_position_name(){
    $adv_position=array(
        'O2O'=>array('O2O_TOP'=>'O2O上屏','O2O_TOPRUN'=>'O2O上屏滚动广告条','O2O_MID'=>'O2O中屏','O2O_DOWN'=>'O2O下屏'),
        'MCP'=>array('MCP_BEFOREPAY'=>'收银台支付前广告','MCP_INPUT'=>'收银台输入金额页','MCP_WAITING'=>'收银台支付等待中广告','MCP_AFTERPAY1'=>'收银台支付后广告1','MCP_AFTERPAY2'=>'收银台支付后广告2'),
        'POS'=>array('POS_LOGIN'=>'POS登陆条','POS_WAITING'=>'POS等待中广告','POS_PREFACE'=>'POS启动广告','POS_INDEX_BANNER'=>'POS首页BANNER','POS_RUNNING_NEWS'=>'POS滚动通知'),
        'MAPP'=>array('MAPP_LOGIN'=>'商户APP','MAPP_WAITING'=>'商户APP等待中广告','MAPP_PREFACE'=>'商户APP启动广告','MAPP_INDEX_BANNER'=>'商户APP首页BANNER','MAPP_RUNNING_NEWS'=>'商户APP滚动通知'),
        'DDSHOW'=>array('DDSHOW_BEGIN'=>'订点秀屏启动广告屏','DDSHOW_RUNNEWS'=>'订点秀屏滚动广告条','DDSHOW_CONTENT'=>'订点秀屏内容页'),
        'WEB'=>array('WEB_INDEX_BANNER'=>'微信门户首页BANNER','WEB_STORE_INDEX_BANNER'=>'商城首页BANNER','WEB_INDEX_SECOND'=>'微信门户秒杀','WEB_INDEX_THREE'=>'微信门户三种商品','WEB_INDEX_RUNNING_NEWS'=>'微信门户滚动通知'),
        'WEBORG'=>array('WEBORG_INDEX_BANNER'=>'机构门户首页BANNER'),
        'WEBNET'=>array('WEBNET_INDEX_BANNER'=>'网点门户首页BANNER'),
        'WEBMER'=>array('WEBMER_INDEX_BANNER'=>'商户门户首页BANNER','WEBMER_RUNNING_NEWS'=>'商户门户滚动消息'),

    );
    return $adv_position;

}
//广告位置
function position_name(){
    $position_name=array(
        'O2O_TOP'=>'O2O上屏','O2O_TOPRUN'=>'O2O上屏滚动广告条','O2O_MID'=>'O2O中屏','O2O_DOWN'=>'O2O下屏',
        'MCP_BEFOREPAY'=>'收银台支付前广告','MCP_INPUT'=>'收银台输入金额页','MCP_WAITING'=>'收银台支付等待中广告','MCP_AFTERPAY1'=>'收银台支付后广告1','MCP_AFTERPAY2'=>'收银台支付后广告2',
        'POS_LOGIN'=>'POS登陆条','POS_WAITING'=>'POS等待中广告','POS_PREFACE'=>'POS启动广告','POS_INDEX_BANNER'=>'POS首页BANNER','POS_RUNNING_NEWS'=>'POS滚动通知',
        'MAPP_LOGIN'=>'商户APP','MAPP_WAITING'=>'商户APP等待中广告','MAPP_PREFACE'=>'商户APP启动广告','MAPP_INDEX_BANNER'=>'商户APP首页BANNER','MAPP_RUNNING_NEWS'=>'商户APP滚动通知',
        'DDSHOW_BEGIN'=>'订点秀屏启动广告屏','DDSHOW_RUNNEWS'=>'订点秀屏滚动广告条','DDSHOW_CONTENT'=>'订点秀屏内容页',
        'WEB_INDEX_BANNER'=>'微信门户首页BANNER','WEB_STORE_INDEX_BANNER'=>'商城首页BANNER','WEB_INDEX_SECOND'=>'微信门户秒杀','WEB_INDEX_THREE'=>'微信门户三种商品','WEB_INDEX_RUNNING_NEWS'=>'微信门户滚动通知',
        'WEBORG_INDEX_BANNER'=>'机构门户首页BANNER',
        'WEBNET_INDEX_BANNER'=>'网点门户首页BANNER',
        'WEBMER_INDEX_BANNER'=>'商户门户首页BANNER','WEBMER_RUNNING_NEWS'=>'商户门户滚动消息',

    );
    return $position_name;

}




//广告形式
function adv_type(){
    $adv_type=array(
        'MCP_BEFOREPAY'=>'PIC',
        'MCP_INPUT'=>'PIC,TEXT',
        'MCP_WAITING'=>'PIC,TEXT',
        'MCP_AFTERPAY1'=>'PICURL,PIC,VIDEO',
        'MCP_AFTERPAY2'=>'PICURL,PIC,VIDEO',
        'O2O_TOP'=>'PIC,URL,TEXT,VIDEO',
        'O2O_TOPRUN'=>'TEXT',
        'O2O_MID'=>'PIC,PICURL,URL,VIDEO',
        'O2O_DOWN'=>'PIC,PICURL,URL,VIDEO',
        'DDSHOW_BEGIN'=>'PIC,URL,TEXT',
        'DDSHOW_RUNNEWS'=>'TEXT',
        'DDSHOW_CONTENT'=>'PIC,PICURL,URL,VIDEO',
        'MAPP_LOGIN'=>'PIC,PICURL',
        'MAPP_WAITING'=>'PIC,PICURL',
        'MAPP_PREFACE'=>'PIC,PICURL',
        'MAPP_INDEX_BANNER'=>'PIC,VIDEO,PICURL',
        'MAPP_RUNNING_NEWS'=>'TEXT',
        'POS_LOGIN'=>'PIC,PICURL',
        'POS_WAITING'=>'PIC,PICURL',
        'POS_PREFACE'=>'PIC,PICURL',
        'POS_INDEX_BANNER'=>'PIC,URL,VIDEO',
        'POS_RUNNING_NEWS'=>'TEXT',
        'WEB_INDEX_BANNER'=>'PIC,VIDEO,PICURL',
        'WEB_STORE_INDEX_BANNER'=>'PIC,VIDEO,PICURL',
        'WEB_INDEX_SECOND'=>'PICURL',
        'WEB_INDEX_THREE'=>'PICURL',
        'WEB_INDEX_RUNNING_NEWS'=>'TEXT',
        'WEBORG_INDEX_BANNER'=>'PIC,VIDEO,PICURL',
        'WEBNET_INDEX_BANNER'=>'PIC,VIDEO,PICURL',
        'WEBMER_INDEX_BANNER'=>'PIC,VIDEO,PICURL',
        'WEBMER_RUNNING_NEWS'=>'TEXT',
    );
    return $adv_type;
}
//广告形式名称
function adv_type_name(){
    $adv_type_name=array(
        'PIC'=>'图像不带连接',
        'PICURL'=>'图像带URL',
        'URL'=>'页面',
        'VIDEO'=>'视频',
        'TEXT'=>'文字不带连接',
    );
    return $adv_type_name;
}


//终端机和广告位置
function tpl_form_term_type_position($values=array(),$position=array()){
    $html = '';
    $option='';
    $term_name=termtype_name();
    foreach ($term_name as $k=>$v) {
        $selected = '';
        if($k == $values['termtype']){
            $selected = 'selected';
        }else{
            $selected = '';
        }
        $option .= "<option value='{$k}' {$selected}>{$v}</option>";

    }

    $html .= '	<div class="form-group">
				<label class="col-xs-12 col-sm-4 col-md-3 col-lg-2 control-label"><span class="text-danger">*</span>终端类型</label>
				<div class="col-sm-8 col-xs-12">
					<select name="termtype" class="form-control required" style="margin-right:15px;" >
						<option value="">--请选择--</option>
						'.$option.'
					</select>	
					<div class="help-block"></div>
				</div>
			</div>
			<div class="form-group">
				<label class="col-xs-12 col-sm-4 col-md-3 col-lg-2 control-label">广告位置</label>
				<div class="col-sm-8 col-xs-12">
					<select name="position" class="form-control tpl-category-parent">
							
					</select>
				</div>	
			</div>
			<div class="form-group">
				<label class="col-xs-12 col-sm-4 col-md-3 col-lg-2 control-label">广告形式</label>
				<div class="col-sm-8 col-xs-12"  id="checkbox">	
				</div>	
			</div>		
			<script type="text/javascript">
			
				 $("select[name=termtype]").change(function(){
					var value = $(this).val();
					var url = "./index.php?c=dt&a=advert_position&do=positionList";
					$.get(url, {"termtype": value,"position_id":"'.$values['position_id'].'"}, function(result, status){
						$("select[name=position]").html(result);
					});
				}).change();
				 $("select[name=position]").change(function(){
					var value = $(this).val();
					var url = "./index.php?c=dt&a=advert_position&do=advTypeList";
					if(value){
						$.get(url, {"position": value,"position_id":"'.$values['position_id'].'"}, function(result, status){
							$("#checkbox").html(result);
						});
					}else{
						$.get(url, {"position":"'.$position['position'].'","position_id":"'.$values['position_id'].'"}, function(result, status){
							$("#checkbox").html(result);
						});
					}
						

					
				}).change();
				
			</script>';
    return $html;
}

//终端选择器
function tpl_form_termtype_name($values=array()){
    $html = '';
    $option='';
    $term_name=termtype_name();

    foreach ($term_name as $k=>$v) {
        $selected = '';
        if($k == $values['termtype']){
            $selected = 'selected';
        }else{
            $selected = '';
        }
        $option .= "<option value='{$k}' {$selected}>{$v}</option>";

    }
    $html .= '<div class="form-group">
				<label class="col-xs-12 col-sm-2 col-md-2 col-lg-1 control-label">终端类型</label>
				<div class="col-sm-8 col-lg-3 col-xs-12">
				<select name="termtype" class="form-control" style="margin-right:15px;" >
					<option value="">--请选择--</option>
					'.$option.'
				</select>	
					<div class="help-block"></div>
				</div>
			</div>';
    return $html;
}
//投放机构相关代码
function toPtions($tree, &$options, $x = 0){
    if (empty($tree)) {
        return;
    }
    if ($x > 0) {
        $last_k = null;
        $last_org = null;
        foreach ($tree as $k => $_org) {
            $last_k = $k;
            $last_org = $_org;
            $tree[$k]['name'] =  str_repeat('│  ', $x -1).'├─'.$_org['name'];
        }

        if (isset($last_k)) {
            $tree[$last_k]['name'] = str_repeat('│  ', $x -1).'└─'.$last_org['name'];
        }
    }

    foreach ($tree as $_org) {
        $childrens = $_org['childrens'];
        unset($_org['childrens']);

        $options[] = $_org;
        toPtions($childrens, $options, $x +1);
    }
}
//投放机构相关代码
function loadSubs($org){
    $orgm = M('dt_org', null);
    $suborgs = $orgm->where(['status'=>1, 'parentcode'=>$org['orgcode']])->select();

    foreach ($suborgs as $k => $suborg) {
        $suborgs[$k] = loadSubs($suborg);
    }

    $org['childrens'] = $suborgs;

    return $org;
}
//投放机构列表
function getAdvOrgList(){
    $orgs = M('dt_org', null)->where(['status'=>1, 'parentcode'=>''])->select();
    foreach ($orgs as $k => $org) {
        $orgs[$k] = loadSubs($org);
    }

    $orglist = [];
    toPtions($orgs, $orglist);
    return $orglist;
}
//投放机构,网点(admin网点广告位,机构网点的网点广告位，admin的机构位终端管理)
function tpl_form_advorgnet($values = array(),$list=true,$all=1) {
    global $_W;
    $html = '';

    if (empty($values) || !is_array($values)) {
        $values = array('adv_orgcode'=>'','adv_netcode'=>'');
    }
    if(empty($values['adv_orgcode'])) {
        $values['adv_orgcode'] = '';
    }
    if(empty($values['adv_netcode'])) {
        $values['adv_netcode'] = '';
    }
    $option = '';

    $orglist = getAdvOrgList();
    if($all==1){
        $allorg=array('orgcode'=>'TOT','name'=>'所有机构');
        array_unshift($orglist,$allorg);
    }
    $orgoptions = array_column($orglist, 'name', 'orgcode');
    if($orgoptions){
        foreach ($orgoptions as $k=>$v) {
            $selected = '';
            if($k === $values['adv_orgcode']){
                $selected = 'selected';
            }else{
                $selected = '';
            }
            $option .= "<option value='{$k}' {$selected}>{$v}</option>";
        }
        $option_title = $list ? '全部' : '请选择';
        $html .= '<div class="form-group">
					<label class="col-xs-12 col-sm-2 col-md-2 control-label">投放机构</label>
				<div class="col-sm-4 col-xs-12">
					<select class="form-control" style="margin-right:15px;" name="adv_orgcode" >
						<option value="">'.$option_title.'</option>
							'.$option.'
						</select>
					</div>
					<label class="col-xs-12 col-sm-1 col-md-1 control-label">投放网点</label>
					<div class="col-sm-4 col-xs-12">
						<select class="form-control" style="margin-right:15px;" name="adv_netcode">
							<option value="">'.$option_title.'</option>
						</select>
					</div>
				</div>
				<script>
				$("select[name=adv_orgcode]").change(function(){
					var value = $(this).val();
					var url = "./index.php?c=dt&a=advert_org&do=advnetlist";
					$.get(url, {"adv_orgcode": value,"adv_netcode":"'.$values['adv_netcode'].'","option_title":"'.$option_title.'","all":"'.$all.'"}, function(result, status){
						$("select[name=adv_netcode]").html(result);
					});
				}).change();
				</script>';

    }


    return $html;
}

//投放机构,网点(机构网点的机构位终端管理)
function tpl_term_advorgnet($values = array(),$list=true,$all=1) {
    global $_W;
    $html = '';

    if (empty($values) || !is_array($values)) {
        $values = array('adv_orgcode'=>'','adv_netcode'=>'');
    }
    if(empty($values['adv_orgcode'])) {
        $values['adv_orgcode'] = '';
    }
    if(empty($values['adv_netcode'])) {
        $values['adv_netcode'] = '';
    }
    $option = '';
    $orglist = CT('org')->where(['status' => 1])->select('orgcode,name');
    if($all==1){
        $orgall['orgcode']='TOT';
        $orgall['name']='所有机构';
        array_unshift($orglist,$orgall);
    }
    $orgoptions = array_column($orglist, 'name', 'orgcode');
    if($_W['user']['idtype'] == 'ORG'){
        $adv_orgcode=pdo_fetchall("select adv_orgcode from dt_adv_org where orgcode=:orgcode",array(":orgcode"=>$_W['user']['orgcode']));
        if($adv_orgcode){
            $advOrg=array();
            foreach($adv_orgcode as $k=>$v){
                $advorg[]=$v['adv_orgcode'];
            }
        }

        $advorg=array_flip($advorg);
        $orgoptions=array_intersect_key($orgoptions,$advorg);
    }elseif($_W['user']['idtype'] == 'NET'){
        $advOrglist=pdo_fetchall("select adv_orgcode from dt_adv_org where orgcode=:orgcode and netcode=:netcode",array(":orgcode"=>$_W['user']['orgcode'],":netcode"=>$_W['user']['netcode']));
        if($adv_orgcode){
            $advOrg=array();
            foreach($adv_orgcode as $k=>$v){
                $advorg[]=$v['adv_orgcode'];
            }
        }

        $advorg=array_flip($advorg);
        $orgoptions=array_intersect_key($orgoptions,$advorg);
    }
    if($orgoptions){
        foreach ($orgoptions as $k=>$v) {
            $selected = '';
            if($k === $values['adv_orgcode']){
                $selected = 'selected';
            }else{
                $selected = '';
            }
            $option .= "<option value='{$k}' {$selected}>{$v}</option>";
        }
        $option_title = $list ? '全部' : '请选择';
        $html .= '<div class="form-group">
					<label class="col-xs-12 col-sm-2 col-md-2 control-label">投放机构</label>
				<div class="col-sm-4 col-xs-12">
					<select class="form-control" style="margin-right:15px;" name="adv_orgcode" >
						<option value="">'.$option_title.'</option>
							'.$option.'
						</select>
					</div>
					<label class="col-xs-12 col-sm-1 col-md-1 control-label">投放网点</label>
					<div class="col-sm-4 col-xs-12">
						<select class="form-control" style="margin-right:15px;" name="adv_netcode">
							<option value="">'.$option_title.'</option>
						</select>
					</div>
				</div>
				<script>
				$("select[name=adv_orgcode]").change(function(){
					var value = $(this).val();
					var url = "./index.php?c=dt&a=advert_org&do=advnetlist";
					$.get(url, {"adv_orgcode": value,"adv_netcode":"'.$values['adv_netcode'].'","option_title":"'.$option_title.'","all":"'.$all.'"}, function(result, status){
						$("select[name=adv_netcode]").html(result);
					});
				}).change();
				</script>';

    }


    return $html;
}