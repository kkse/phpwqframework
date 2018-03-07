<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/11/22
 * Time: 11:05
 */

namespace kkse\wqframework\dtwq\examine;

/**
 * 审核业务数据结构
 * Class Data
 * @package dtwq\examine
 */
class Data
{
    protected $pkid;
    protected $add_data;
    protected $edit_data;
    protected $old_data;

    protected function __construct(array $data)
    {
        $this->pkid = $data['pkid'];
        !empty($data['add']) && is_array($data['add'])
        and $this->add_data = $data['add'];

        !empty($data['edit']) && is_array($data['edit'])
        and $this->edit_data = $data['edit'];

        !empty($data['old']) && is_array($data['old'])
        and $this->old_data = $data['old'];
    }

    /**
     * @return mixed
     */
    public function getPkid()
    {
        return $this->pkid;
    }

    public function getNewData()
    {
        if ($this->add_data) {
            return $this->add_data;
        }

        $new_data = $this->old_data;
        $this->edit_data and $new_data = $this->edit_data + $new_data;

        return $new_data;
    }

    public function getOriginalData()
    {
        if ($this->add_data) {
            return $this->add_data;
        }

        if ($this->old_data) {
            return $this->old_data;
        }
        return [];
    }



    /**
     * @return mixed
     */
    public function getAddData()
    {
        return $this->add_data;
    }

    /**
     * @return mixed
     */
    public function getEditData()
    {
        return $this->edit_data;
    }

    /**
     * @return mixed
     */
    public function getOldData()
    {
        return $this->old_data;
    }



    public static function create(array $data)
    {
        if (empty($data['pkid']) || !is_scalar($data['pkid'])) {
            return null;
        }

        if ((empty($data['add']) || !is_array($data['add']))
            && (empty($data['old']) || !is_array($data['old']))) {
            return null;
        }

        return new self($data);
    }

    public static function load($data_str)
    {
        if (!is_string($data_str)) {
            return null;
        }

        $data = json_decode($data_str, true);
        if (!is_array($data)) {
            return null;
        }

        return self::create($data);
    }

    public function __toString()
    {
        $data = ['pkid'=>$this->pkid];
        $this->add_data and $data['add'] = $this->add_data;
        $this->edit_data and $data['edit'] = $this->edit_data;
        $this->old_data and $data['old'] = $this->old_data;

        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}