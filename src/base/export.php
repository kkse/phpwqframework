<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/3/1
 * Time: 16:04
 */

namespace kkse\wqframework\base;


class export
{
    protected static function loadColumns($param, $platform = '')
    {
        $platform and $columns_file = IA_ROOT .'/data/export/'.$param.'.'.$platform.'.php';
        empty($columns_file) || !is_file($columns_file)
        and $columns_file = IA_ROOT .'/data/export/'.$param.'.php';

        is_file($columns_file) and $columns = include($columns_file);

        isset($columns) && is_array($columns)
        or $columns = false;

        return $columns;
    }

    public static  function check($list,$param,$title,$platform = '')
    {
        $columns = self::loadColumns($param, $platform);
        export($list, array(
                "title" => $title,
                "columns" => $columns)
        );
        return;
    }

    public static  function check_batch($list,$param,$title,$platform = '')
    {
        $columns = self::loadColumns($param, $platform);

        $info = [
            'list' =>$list,
            "title" => $title,
            "columns" => $columns
        ];
        return $info;
    }
}