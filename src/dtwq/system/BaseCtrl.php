<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/11/3
 * Time: 17:38
 */

namespace kkse\wqframework\dtwq\system;

use kkse\wqframework\dtwq\admin\Visitor;
use think\Request;

abstract class BaseCtrl
{
    protected $user;       //登录用户
    protected $webSite;    //后台网页处理
    protected $ascription; //所属处理器

    public function __construct($check = true)
    {
        $this->webSite = WebSite::getRuning();
        $this->user = $this->webSite?$this->webSite->getUser():Visitor::getInstance()->getUser();
        $this->ascription = $this->webSite->getAscription();
        $check and $this->__check();
    }

    protected function __check()
    {
        if (!$this->user || !$this->webSite) {
            message('抱歉，您无权进行该操作，请先登录！!', url('user/login'), 'warning');
        }
    }

    protected function _getAscription($isOld = false) {
        $idtype = get_gpc("idtype")
        or message('请选择所属类型！', '', 'error');

        $idstr = get_gpc("idvalue")
        or message('请选择所属！', '', 'error');

        $this->ascription->hasDroit($idtype, $idstr)
        or message('无权管理当前所属！', '', 'error');

        if (!$isOld) {
            return [$idtype, $idstr];
        }

        list($orgcode, $netcode) = $this->ascription->substitution($idtype, $idstr);
        return [$orgcode, $netcode];
    }

    protected function _getFromToken($name = '__token__')
    {
        return Request::instance()->token($name);
    }
}