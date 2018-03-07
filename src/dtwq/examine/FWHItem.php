<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/12/7
 * Time: 17:59
 */

namespace kkse\wqframework\dtwq\examine;

/**
 * 审核流水历史表对象
 * Class FWHItem
 * @package dtwq\examine
 */
class FWHItem extends FWItem
{
    protected $id;
    public function getId()
    {
        return $this->id;
    }

    public function backup($backup = true)
    {
        return false;
    }

    public function update(array $update, array $add_where = [])
    {
        return false;
    }

    public function updateData(Data $data)
    {
        return false;
    }

    public function submit()
    {
        return false;
    }

    public static function getInstance($id)
    {
        $data = pdo_get(config('DB_PREFIX').'examine_flowing_water_history', [
            'id'=>$id
        ]);

        if (!$data) {
            return null;
        }

        return new self($data);
    }
}