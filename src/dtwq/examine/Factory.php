<?php
namespace kkse\wqframework\dtwq\examine;


use kkse\wqframework\dtwq\admin\User;
use Org\Businessman\BusinessmanAuth;
use think\Loader;

class Factory
{
    /**
     * @param $business_key
     * @param $action
     * @param array $data
     * @param $user
     * @return FlowingWater|null
     */
    public static function createFlowingWater($business_key, $action, array $data, $user)
    {
        if (!($user instanceof User || $user instanceof BusinessmanAuth)) {
            return null;
        }

        $author = new Author($user);
        $business = self::getBusiness($business_key);

        if (!$business) {
            return null;
        }
        $data = $business->getExamineData($action, $data);

        return FlowingWater::createInstance($business, $action, $data, $author);
    }

    public static function getFlowingWater(FWItem $fw_item)
    {
        return FlowingWater::loadInstance($fw_item);
    }

    /**
     * @param $business_key
     * @return ABusiness
     */
    public static function getBusiness($business_key)
    {
        $class = __NAMESPACE__ .'\\business\\'.Loader::parseName($business_key, 1);

        if (!is_subclass_of($class, ABusiness::class)) {
            return null;
        }

        return new $class();
    }
}