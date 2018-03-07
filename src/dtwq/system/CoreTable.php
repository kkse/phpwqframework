<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/11/2
 * Time: 22:21
 */

namespace kkse\wqframework\dtwq\system;
use Com\Internal\DbCache;

/**
 * 缓存数据权限配置表
 * Class CoreTable
 * @package dtwq\system
 */
class CoreTable
{
    public function __construct()
    {
    }

    public function getTableInfo($tablename)
    {
        $data = DbCache::data_get(tablename('core_table', false), $tablename);
        if ($data && !is_array($data['data_control'])) {
            $data['data_control'] = json_decode($data['data_control'], true);
            DbCache::cache_update(tablename('core_table', false), $tablename, ['data_control'=>$data['data_control']]);
        }
        return $data;
    }

    public function deleteCache($tablename)
    {
        DbCache::cache_delete(tablename('core_table', false), $tablename);
    }
}