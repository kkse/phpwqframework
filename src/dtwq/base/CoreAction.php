<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/1/22
 * Time: 12:21
 */

namespace kkse\wqframework\dtwq\base;

use lang\RowObject;

class CoreAction extends RowObject
{
    public function getMode()
    {
        return $this->_data['mode'];
    }

    public function getController()
    {
        return $this->_data['controller'];
    }

    public function getControl()
    {
        return $this->_data['control'];
    }

    public function getDataControl()
    {
        return $this->_data['data_control'];
    }


    public function getAction()
    {
        return $this->_data['action'];
    }

    public function getDo()
    {
        return $this->_data['do'];
    }


    //当是模块时，getModule和getOp才会有值
    public function isModule()
    {
        return !empty($this->_data['is_module']);
    }

    public function getModule()
    {
        return isset($this->_data['module'])?$this->_data['module']:'';
    }

    public function getOp()
    {
        return isset($this->_data['op'])?$this->_data['op']:'';
    }

    public function getName()
    {
        return $this->_data['name'];
    }

    public function isUnknow()
    {
        return !empty($this->_data['is_unknow']);
    }

    public function getFinalCoreAction()
    {
        if (empty($this->_data['default_val'])) {
            return $this;
        }
        if ($this->isModule()) {
            if (!$this->getModule()) {
                $ca = self::getModuleAction($this->getMode(), $this->_data['default_val'], $this->getAction(), $this->getDo(), $this->getOp());
            } elseif (!$this->getAction()) {
                $ca = self::getModuleAction($this->getMode(), $this->getModule(), $this->_data['default_val'],  $this->getDo(), $this->getOp());
            } elseif (!$this->getDo()) {
                $ca = self::getModuleAction($this->getMode(), $this->getModule(), $this->getAction(), $this->_data['default_val'], $this->getOp());
            } elseif (!$this->getOp()) {
                $ca = self::getModuleAction($this->getMode(), $this->getModule(), $this->getAction(),  $this->getDo(), $this->_data['default_val']);
            } else {
                return $this;
            }
        } else {
            if (!$this->getController()) {
                $ca = self::getCoreAction($this->getMode(), $this->_data['default_val'], $this->getAction(), $this->getDo());
            } elseif (!$this->getAction()) {
                $ca = self::getCoreAction($this->getMode(), $this->getController(), $this->_data['default_val'],  $this->getDo());
            } elseif (!$this->getDo()) {
                $ca = self::getCoreAction($this->getMode(), $this->getController(), $this->getAction(), $this->_data['default_val']);
            } else {
                return $this;
            }
        }

        return $ca->getFinalCoreAction();
    }

    public function deleteCache()
    {
        if ($this->isModule()) {
            $cache_key = self::getModuleCacheKey($this->getMode(), $this->getModule(), $this->getAction(),  $this->getDo(), $this->getOp());
        } else {
            $cache_key = self::getCacheKey($this->getMode(), $this->getController(), $this->getAction(), $this->getDo());
        }

        S($cache_key, null);
    }

    protected static function getCacheKey($mode, $controller, $action, $do)
    {
        $cache_key = sprintf('core_action:%s:%s:%s:%s', $mode, $controller?:'[empty]', $action?:'[empty]', $do?:'[empty]');
        return $cache_key;
    }

    protected static function getModuleCacheKey($mode, $module, $action, $do, $op = '')
    {
        $cache_key = sprintf('core_action:%s:%s:%s:%s:%s', $mode.'.module', $module?:'[empty]', $action?:'[empty]', $do?:'[empty]', $op?:'[empty]');
        return $cache_key;
    }

    public static function currentCoreAction($mode)
    {
        global $_GPC;
        $query = $_GPC;

        $m = empty($query['m'])?'':$query['m'];
        $c = empty($query['c'])?'':$query['c'];
        $a = empty($query['a'])?'':$query['a'];
        $do = empty($query['do'])?'':$query['do'];
        $op = empty($query['op'])?'':$query['op'];

        if ($c == 'module') {
            return self::getModuleAction($mode, $m, $a, $do, $op);
        } else {
            return self::getCoreAction($mode, $c, $a, $do);
        }
    }

    public static function findCoreAction($mode, $url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        if (!(is_null($path) || in_array($path, ['./index.php', 'index.php', "/{$mode}/index.php"]))) {
            return null;//不是自己的结构
        }
        $url_query = parse_url($url, PHP_URL_QUERY);
        parse_str($url_query, $query);

        $m = empty($query['m'])?'':$query['m'];
        $c = empty($query['c'])?'':$query['c'];
        $a = empty($query['a'])?'':$query['a'];
        $do = empty($query['do'])?'':$query['do'];
        $op = empty($query['op'])?'':$query['op'];

        if ($c == 'module') {
            return self::getModuleAction($mode, $m, $a, $do, $op);
        } else {
            return self::getCoreAction($mode, $c, $a, $do);
        }
    }

    public static function autoCoreAction($mode, $controller, $action, $do)
    {
        $modes = explode('.', $mode);
        if (count($modes) > 2) {//不允许配置错误
            return null;
        }

        if (count($modes) > 1 && $modes[1] == 'module')  {
            list($do, $op) = explode('.', $do.'.');
            return self::getModuleAction($modes[0], $controller, $action, $do, $op);
        } else {
            return self::getCoreAction($mode, $controller, $action, $do);
        }
    }

    public static function getCoreAction($mode, $controller, $action, $do)
    {
        $cache_key = self::getCacheKey($mode, $controller, $action, $do);
        $data = S($cache_key);
        if (!$data) {
            $row = pdo_get('core_action', [
                'mode'=>$mode,
                'controller' => $controller,
                'action' => $action,
                'do' => $do
            ]);
            if ($row) {
                if ($row['data_control']) {
                    $row['data_control'] = json_decode($row['data_control'], true);
                }
                //$row['url'] = $row['controller'].'/'.$row['action'].'/'.$row['do'];
                $data = $row;
            } else {
                $data = config('CACHE_FAIL_DATA');
            }

            S($cache_key, $data);
        }

        if (is_array($data)) {
            return new self($data);
        }
        return self::getUnknowAction($mode, $controller, $action, $do);
    }

    public static function getModuleAction($mode, $module, $action, $do, $op = '')
    {
        if (!$module) {//模块不能为空
            return null;
        }

        $cache_key = self::getModuleCacheKey($mode, $module, $action, $do, $op);
        $data = S($cache_key);
        if (!$data) {
            $row = pdo_get('core_action', [
                'mode'=>$mode.'.module',
                'controller' => $module,
                'action' => $action,
                'do' => $do.($op?('.'.$op):''),
            ]);
            if ($row) {
                if ($row['data_control']) {
                    $row['data_control'] = json_decode($row['data_control'], true);
                }
                //$row['url'] = $row['controller'].'/'.$row['action'].'/'.$row['do'];
                $row['is_module'] = '1';
                $row['module'] = $module;
                $row['mode'] = $mode;
                $row['controller'] = 'module';
                $row['do'] = $do;
                $row['op'] = $op;

                $data = $row;
            } else {
                $data = config('CACHE_FAIL_DATA');
            }

            S($cache_key, $data);
        }

        if (is_array($data)) {
            return new self($data);
        }
        return self::getUnknowModuleAction($mode, $module, $action, $do, $op);
    }

    protected static function getUnknowAction($mode, $controller, $action, $do)
    {
        $data = [
            'mode'=>$mode,
            'controller'=>$controller,
            'action'=>$action,
            'do'=>$do,
            //'url'=>$controller.'/'.$action.'/'.$do,
            'name'=>'未定义动作',
            'control'=>'admin',
            'data_control'=>'',
            'default_val'=>'',
            'make'=>'',
            'is_unknow' => '1',
        ];

        return new self($data);
    }

    protected static function getUnknowModuleAction($mode, $module, $action, $do, $op = '')
    {
        $data = [
            'mode'=>$mode,
            'controller'=>'module',
            'action'=>$action,
            'do'=>$do,
            'op'=>$op,
            'module'=>$module,
            //'url'=>'module/'.$action.'/'.$do,
            'name'=>'未定义动作',
            'control'=>'admin',
            'data_control'=>'',
            'default_val'=>'',
            'make'=>'',
            'is_module'=>'1',
            'is_unknow' => '1',
        ];

        return new self($data);
    }
}