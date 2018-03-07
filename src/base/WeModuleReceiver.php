<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/3/1
 * Time: 11:19
 */

namespace kkse\wqframework\base;


abstract class WeModuleReceiver extends WeBase {

    public $params;

    public $response;

    public $keyword;

    public $message;

    abstract function receive();
}