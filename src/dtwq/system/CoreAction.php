<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/11/2
 * Time: 22:21
 */

namespace kkse\wqframework\dtwq\system;

/**
 * 缓存动作
 * Class CoreAction
 * @package dtwq\system
 */
class CoreAction
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

    protected function getCacheKey($controller, $action, $do)
    {
        $cache_key = sprintf('core_action:%s:%s:%s', $controller?:'[empty]', $action?:'[empty]', $do?:'[empty]');
        return $cache_key;
    }

    protected function getUnknowAction($controller, $action, $do)
    {
        return [
            'mode'=>$this->mode,
            'controller'=>$controller,
            'action'=>$action,
            'do'=>$do,
            'url'=>$controller.'/'.$action.'/'.$do,
            'name'=>'未定义动作',
            'control'=>'admin',
            'data_control'=>'',
            'default_val'=>'',
            'make'=>'',
        ];
    }

    public function getActionInfo($controller, $action, $do)
    {
        if (!isset(self::$core_actions[$this->mode][$controller][$action][$do])) {
            $cache_key = $this->getCacheKey($controller, $action, $do);
            $data = S($cache_key);
            if (!$data) {
                $row = pdo_get('core_action', [
                    'mode'=>$this->mode,
                    'controller' => $controller,
                    'action' => $action,
                    'do' => $do
                ]);
                if ($row) {
                    if ($row['data_control']) {
                        $row['data_control'] = json_decode($row['data_control'], true);
                    }
                    $row['url'] = $row['controller'].'/'.$row['action'].'/'.$row['do'];

                    $data = $row;
                } else {
                    $data = config('CACHE_FAIL_DATA');
                }

                S($cache_key, $data);
            }

            if (!is_array($data)) {
                self::$core_actions[$this->mode][$controller][$action][$do] = false;
            } else {
                self::$core_actions[$this->mode][$controller][$action][$do] = $data;
            }
        }

        $core_action = self::$core_actions[$this->mode][$controller][$action][$do];
        if (!$core_action) {
            return $this->getUnknowAction($controller, $action, $do);
        }

        if (empty($controller)) {
            if ($core_action['default_val']) {
                return $this->getActionInfo($core_action['default_val'], $action, $do);
            }
        } elseif (empty($action)) {
            if ($core_action['default_val']) {
                return $this->getActionInfo($controller, $core_action['default_val'], $do);
            }
        } elseif (empty($do)) {
            if ($core_action['default_val']) {
                return $this->getActionInfo($controller, $action, $core_action['default_val']);
            }
        }

        return $core_action;
    }

    public function deleteCache($controller, $action, $do)
    {
        $cache_key = $this->getCacheKey($controller, $action, $do);
        S($cache_key, null);
    }
}