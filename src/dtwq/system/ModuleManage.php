<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/2/7
 * Time: 10:10
 */

namespace kkse\wqframework\dtwq\system;

/**
 * 模块管理
 * Class ModuleManage
 * @package dtwq\system
 */
class ModuleManage
{
    /**
     * 获取系统模块列表
     * @return array
     */
    public static function system_modules() {
        return array(
            'basic', 'news', 'music', 'userapi',
            'custom', 'images', 'video', 'voice', 'chats', 'wxcard'
        );
    }

    public static function bindings()
    {
        static $bindings = array(
            'cover' => array(
                'name' => 'cover',
                'title' => '功能封面',
                'desc' => '功能封面是定义微站里一个独立功能的入口(手机端操作), 将呈现为一个图文消息, 点击后进入微站系统中对应的功能.'
            ),
            'rule' => array(
                'name' => 'rule',
                'title' => '规则列表',
                'desc' => '规则列表是定义可重复使用或者可创建多次的活动的功能入口(管理后台Web操作), 每个活动对应一条规则. 一般呈现为图文消息, 点击后进入定义好的某次活动中.'
            ),
            'menu' => array(
                'name' => 'menu',
                'title' => '管理中心导航菜单',
                'desc' => '管理中心导航菜单将会在管理中心生成一个导航入口(管理后台Web操作), 用于对模块定义的内容进行管理.'
            ),
            'home' => array(
                'name' => 'home',
                'title' => '微站首页导航图标',
                'desc' => '在微站的首页上显示相关功能的链接入口(手机端操作), 一般用于通用功能的展示.'
            ),
            'profile'=> array(
                'name' => 'profile',
                'title' => '微站个人中心导航',
                'desc' => '在微站的个人中心上显示相关功能的链接入口(手机端操作), 一般用于个人信息, 或针对个人的数据的展示.'
            ),
            'shortcut'=> array(
                'name' => 'shortcut',
                'title' => '微站快捷功能导航',
                'desc' => '在微站的快捷菜单上展示相关功能的链接入口(手机端操作), 仅在支持快捷菜单的微站模块上有效.'
            ),
            'function'=> array(
                'name' => 'function',
                'title' => '微站独立功能',
                'desc' => '需要特殊定义的操作, 一般用于将指定的操作指定为(direct). 如果一个操作没有在具体位置绑定, 但是需要定义为(direct: 直接访问), 可以使用这个嵌入点'
            )
        );
        return $bindings;
    }
}