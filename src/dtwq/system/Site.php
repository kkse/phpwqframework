<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/3/2
 * Time: 11:32
 */

namespace kkse\wqframework\dtwq\system;


use kkse\wqframework\base\Loader;

class Site
{
    public static function run()
    {
        global $_W;
        is_array($_W) or $_W = [];
        $_W['site'] = new self();
        return $_W['site'];
    }

}