<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/11/3
 * Time: 15:13
 */

namespace kkse\wqframework\dtwq\system;
use Com\Internal\DbCache;
use kkse\wqframework\dtwq\admin\User;

/**
 * 系统内部菜单
 * Class SystemMenu
 * @package dtwq\system
 */
class SystemMenu
{
    protected static $core_actions;//获取的数据缓存
    protected $mode;

    public function __construct($mode = 'web')
    {
        $this->setMode($mode);
    }

    public function setMode($mode)
    {
        $this->mode = $mode;
        return $this;
    }



    public function getMenuPairs(User $user)
    {
        $cache_key = sprintf('system.menu.pairs:%s:%s', $this->mode, $user->getUid());
        $data = S($cache_key);
        if (!$data) {
            $pairs = pdo_getpairs('system_menu', [
                'menu_mode'=>$this->mode,
                'creator_uid' => $user->getUid(),
            ], 'menu_id', 'menu_name');
            if ($pairs) {
                $data = $pairs;
            } else {
                $data = config('CACHE_FAIL_DATA');
            }

            S($cache_key, $data);
        }

        if (is_array($data)) {
            return $data;
        }

        return [];
    }

    public function getMenuInfo($menu_id)
    {
        $data = DbCache::data_get(tablename('system_menu', false), $menu_id);
        if ($data && !is_array($data['menu_data'])) {
            $data['menu_data'] = json_decode($data['menu_data'], true);
            DbCache::cache_update(tablename('system_menu', false), $menu_id, ['menu_data'=>$data['menu_data']]);
        }

        if ($data['menu_mode'] != $this->mode) {
            return false;
        }
        return $data;
    }

    public function deleteCache($menu_id)
    {
        DbCache::cache_delete(tablename('system_menu', false), $menu_id);
    }

    public function deletePublic()
    {

    }

    public static function getMenuName($menu_id)
    {
        return DbCache::data_getcolumn(tablename('system_menu', false), $menu_id, 'menu_name');
    }
}