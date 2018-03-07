<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2017/12/27
 * Time: 15:27
 */

namespace kkse\wqframework\dtwq\system;

/**
 * 搜索控制类
 * Class Search
 * @package dtwq\system
 */
class Search
{
    protected $title = '筛选';
    protected $export = false;//是否存在导出按钮
    protected $items = [];//查询条件限制
    protected $hidden_items = [];//隐形并固定的查询参数:例如c\a\do

    public function __construct()
    {
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return bool
     */
    public function isExport()
    {
        return $this->export;
    }

    /**
     * @param bool $export
     */
    public function setExport($export)
    {
        $this->export = $export;
    }

    public function addItems(array $items)
    {
        $this->items = $items+$this->items;
    }

    public function delItem($item_key)
    {
        unset($this->items[$item_key]);
    }

    public function addHiddenItems(array $items)
    {
        $this->hidden_items = $items+$this->hidden_items;
    }

    public function delHiddenItem($item_key)
    {
        unset($this->hidden_items[$item_key]);
    }

    public function hasSearch()
    {
        return !empty($this->items);
    }

    public function getDataValue()
    {
        $data = [];
        foreach ($this->items as $item) {
            $value = get_gpc($item['field'], '', 'trim');
            $value === '' or $data[$item['field']] = $value;
        }
        return $data;
    }

    public function getSearchInfo()
    {
        list ($items, $hasSearch) = $this->getSearchItems();
        $info = [
            'title'=>$this->title,
            'export'=>$this->export,
            'hidden_items'=>$this->hidden_items,
            'items'=>$items,
            'has_search'=>$hasSearch,
        ];

        return $info;
    }

    protected function getSearchItems()
    {
        $items = [];
        $hasSearch = false;
        foreach ($this->items as $item) {
            $item['value'] = get_gpc($item['field'], '', 'trim');
            $item['value'] === '' or  $hasSearch = true;
            $items[] = $item;
        }
        return [$items, $hasSearch];
    }
}