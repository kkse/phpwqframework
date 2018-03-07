<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/11/8
 * Time: 23:49
 */

namespace kkse\wqframework\dtwq\admin;

//归属类，用于管理数据归属用的,后台归属
use kkse\wqframework\dtwq\base\Net;
use kkse\wqframework\dtwq\base\Org;

class Ascription
{
    protected $types = ['ORG'=>'机构', 'NET'=>'网点'];
    protected $user;
    protected $ascription_set;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->ascription_current = session('ascription_set');

        if (!$this->ascription_current) {
            $this->ascription_current = $this->getBaseInfos();
            session('ascription_set', $this->ascription_current);
        }
    }

    //检查是否有所属权利
    public function hasDroit($key, $value)
    {
        if ($this->user->isAdministrator()) {
            return true;
        } elseif ($this->user->isOrg()) {
            if ($key == 'ORG') {
                return Org::isSubOf($value, $this->user->getOrgcode());
            } elseif ($key == 'NET') {
                $net = Net::getNet($value);
                if ($net && Org::isSubOf($net['orgcode'], $this->user->getOrgcode())) {
                    return true;
                }
            }
        } elseif ($this->user->isNet() && $key == 'NET') {
            return Net::isSubOf($value, $this->user->getNetcode());
        }

        return true;
    }

    /**
     * 处理旧的所属
     * @param $key
     * @param $value
     * @return array
     */
    public function substitution($key, $value)
    {
        $orgcode = '';
        $netcode = '';
        if ($key == 'NET') {
            $netcode = $value;
            $orgcode = Net::getOrgcode($netcode);
        } elseif ($key == 'ORG') {
            $orgcode = $value;
        }

        return [$orgcode, $netcode];
    }

    /**
     * 旧的所属转义为新的模式
     * @param $orgcode
     * @param $netcode
     * @return array
     */
    public function translation($orgcode, $netcode)
    {
        if ($netcode && !in_array($netcode, ['TOT', 'SYS'])) {
            return ['NET', $netcode];
        }

        if ($orgcode && !in_array($netcode, ['TOT', 'SYS'])) {
            return ['ORG', $orgcode];
        }

        return ['SYS', 'SYS'];
    }

    public function setValue($key, $value)
    {
        if ($this->hasDroit($key, $value)) {
            switch ($key) {
                case 'ORG':
                    if ($this->ascription_current['ORG']['value'] != $value) {
                        $org_text = $value?Org::getOrgName($value):'全部';
                        $this->ascription_current['ORG'] = ['title'=>'机构',
                            'key'=>'ORG','value'=>$value,
                            'text'=>$org_text,
                            'switch'=>true];

                        //只要切换机构，网点就要是全部的。
                        $this->ascription_current['NET'] = ['title'=>'网点',
                            'key'=>'NET',
                            'value'=>'',
                            'text'=>'全部',
                            'switch'=>true
                        ];
                        session('ascription_set', $this->ascription_current);
                    }
                    break;
                case 'NET':
                    $new_netcode = $value;
                    $old_netcode = $this->ascription_current['NET']['value'];
                    if ($old_netcode != $new_netcode) {
                        $net_text = $new_netcode?Net::getNetName($new_netcode):'全部';
                        $this->ascription_current['NET'] = ['title'=>'网点',
                            'key'=>'NET',
                            'value'=>$new_netcode,
                            'text'=>$net_text,
                            'switch'=>true
                        ];

                        if ($new_netcode) {
                            $new_net = Net::getNet($new_netcode);
                            if ($this->ascription_current['ORG']['value'] != $new_net['orgcode']) {

                                $this->ascription_current['ORG'] = ['title'=>'机构',
                                    'key'=>'ORG',
                                    'value'=>$new_net['orgcode'],
                                    'text'=>Org::getOrgName($new_net['orgcode']),
                                    'switch'=>true
                                ];
                            }
                        }

                        session('ascription_set', $this->ascription_current);
                    }
                    break;
            }
        }
        return $this;
    }

    public function getBaseInfos()
    {
        if ($this->user->isAdministrator()) {
            $ascription_current = [
                'ORG'=>['title'=>'机构','key'=>'ORG','value'=>'', 'text'=>'全部','switch'=>true],
                'NET'=>['title'=>'网点','key'=>'NET','value'=>'', 'text'=>'全部','switch'=>true],
            ];
        } elseif ($this->user->isOrg()) {
            $orgcode = $this->user->getOrgcode();
            $ascription_current = [
                'ORG'=>['title'=>'机构','key'=>'ORG','value'=>$orgcode, 'text'=>Org::getOrgName($orgcode),'switch'=>!!Org::getOrgChildren($orgcode)],
                'NET'=>['title'=>'网点','key'=>'NET','value'=>'', 'text'=>'全部','switch'=>true],
            ];
        } elseif ($this->user->isNet()) {
            $net_orgcode = $this->user->getOrgcode();
            $netcode = $this->user->getNetcode();
            $ascription_current = [
                'ORG'=>['title'=>'机构','key'=>'ORG','value'=>$net_orgcode, 'text'=>Org::getOrgName($net_orgcode),'switch'=>false],
                'NET'=>['title'=>'网点','key'=>'NET','value'=>$netcode, 'text'=>Net::getNetName($netcode),'switch'=>!!Net::getNetChildren($netcode)],
            ];
        } else {
            $ascription_current = [];
        }

        return $ascription_current;
    }

    public function getInfos()
    {
        return $this->ascription_current;
    }

    public function getBreadcrumbList($key, $value)
    {
        $list = [];
        if ($this->hasDroit($key, $value)) {
            switch ($key) {
                case 'ORG':
                    $pcode = $this->user->isAdministrator()?'':$this->user->getOrgcode();
                    $list = Org::getAllParent($value, $pcode);
                    break;
                case 'NET':
                    $pcode = $this->user->isNet()?$this->user->getNetcode():'';
                    $list =  Net::getAllParent($value, $pcode);
                    break;
            }
        }

        $list = [''=>'全部'] + $list;
        return $list;
    }
    public function getCurrentValue($isOld = false)
    {
        $orgcode = $this->ascription_current['ORG']['value'];
        $netcode = $this->ascription_current['NET']['value'];
        if ($isOld) {
            return [$orgcode, $netcode];
        }
        return $this->translation($orgcode, $netcode);
    }


    public function getCurrentInfos()
    {
        //$this->ascription_current['ORG'];
    }

    public function getItemOrgcode($key, $value)
    {
        if ($key == 'ORG') {
            return $value;
        } elseif ($key == 'NET') {
            $net = Net::getNet($value);
            if ($net) {
                return $net['orgcode'];
            }
        }

        return false;
    }

    public function getInputInfo($key, $value)
    {
        $info = [
            'select'=>$key,
            'value'=>$value,
            'text'=>$this->getItemText($key, $value),
            'list'=>[],
        ];

        if ($this->user->isAdministrator() || $this->user->isOrg()) {
            $info['list'][] = ['title'=>'机构', 'key'=>'ORG'];
        }

        $info['list'][] = ['title'=>'网点', 'key'=>'NET'];

        if (!$key) {
            $item = reset($info['list']);
            $info['select'] = $item['key'];
        }

        return $info;
    }

    public function getItemText($key, $value)
    {
        if (!$value) {
            return ':';
        }

        if ($key == 'ORG') {
            return $value.':'.Org::getOrgName($value);
        } elseif ($key == 'NET') {
            return $value.':'.Net::getNetName($value);
        }
        return $value.':未知';
    }

    public function getOldItemText($orgcode, $netcode)
    {
        list($key, $value) = $this->translation($orgcode, $netcode);
        $text = isset($this->types[$key])?($this->types[$key].':'):'';
        return $text.$this->getItemText($key, $value);
    }
}