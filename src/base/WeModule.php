<?php
/**
 * Created by PhpStorm.
 * User: kkse
 * Date: 2018/3/1
 * Time: 11:18
 */

namespace kkse\wqframework\base;


abstract class WeModule extends WeBase {

    public function fieldsFormDisplay($rid = 0) {
        return '';
    }

    public function fieldsFormValidate($rid = 0) {
        return '';
    }

    public function fieldsFormSubmit($rid) {
    }

    public function ruleDeleted($rid) {
        return true;
    }

    public function settingsDisplay($settings) {
    }
}