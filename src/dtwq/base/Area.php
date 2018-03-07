<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/11/23
 * Time: 17:41
 */

namespace kkse\wqframework\dtwq\base;


use Com\Internal\DbCache;
use lang\RowObject;

class Area extends RowObject
{
    public static function getArea($area_id)
    {
        $data = DbCache::data_get('dt_pub_area_pay', $area_id);
        if ($data) {
            return new self($data);
        }
        return null;
    }

    public static function getName($area_id)
    {
        $obj = self::getArea($area_id);
        return $obj?$obj['name']:'未知';
    }

    public static function getFullName($area_id)
    {
        $all = self::getAllParent($area_id);
        return implode(' ', $all);
    }

    public function getProvince($is_name = false)
    {
        $province_code = substr($this->_data['id'], 0, 2).'0000';
        if ($is_name) {
            return self::getName($province_code);
        }
        return $province_code;
    }

    public function getCity($is_name = false)
    {
        if (substr($this->_data['id'], 2, 2) == '00') {
            return '';
        }
        $city_code = substr($this->_data['id'], 0, 4).'00';
        if ($is_name) {
            return self::getName($city_code);
        }
        return $city_code;
    }

    public function getDistrict($is_name = false)
    {
        if (substr($this->_data['id'], 4, 2) == '00') {
            return '';
        }

        if (strlen($this->_data['id']) > 6) {
            $city_code = substr($this->_data['id'], 0, 6);
            if ($is_name) {
                return self::getName($city_code);
            }
            return $city_code;
        }
        if ($is_name) {
            return $this->_data['name'];
        }
        return $this->_data['id'];
    }

    public static function getAllParent($area_id, $area_pid = '1'){
        if (!$area_id) {
            return [];
        }

        if ($area_id == $area_pid) {
            if ($area_pid == '1') {
                return [];
            }
            return [$area_id=>Org::getOrgName($area_id)];
        }

        $obj = self::getArea($area_id);
        if (!$obj) {
            return [];
        }

        if ($obj['pid']  && $obj['pid'] !== '1') {
            $plist = self::getAllParent($obj['pid'], $area_pid);
        } else {
            $plist = [];
        }

        $plist[$area_id] = $obj['name'];
        return $plist;
    }
}