<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/4
 * Time: 10:23
 */

namespace app\Controllers\CXK;


class TestMqtt extends CXK
{
    public function initialization($controller_name, $method_name)
    {
        parent::initialization($controller_name, $method_name); // TODO: Change the autogenerated stub
    }

    public function http_TestMqtt()
    {
        $result = yield api('Kefu')->mqtt(0);
        $this->end($result);
    }
}