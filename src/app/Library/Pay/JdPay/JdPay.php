<?php
/**
 * Created by FPHD.COM.
 * User: chenqianhao
 * Date: 2018/05/21
 * Time: 下午15:03
 */

namespace app\Library\Pay\JdPay;

class JdPay
{
    protected static $config = [];

    public static function config($config=[])
    {
        self::$config = $config;
    }
}