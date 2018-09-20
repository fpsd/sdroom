<?php
/**
 * Created by FPWAN.COM.
 * User: linmaogan
 * Date: 2018/4/14
 * Time: 下午4:15
 */

$config['mysql']['enable'] = true;

$config['mysql']['active'] = 'kefu';

/**
 * 客服
 */
$config['mysql']['kefu']['enable'] = true;
$config['mysql']['kefu']['host'] = "127.0.0.1";
$config['mysql']['kefu']['port'] = '3306';
$config['mysql']['kefu']['user'] = 'root';
$config['mysql']['kefu']['password'] = 'root';
$config['mysql']['kefu']['database'] = 'kefu';
$config['mysql']['kefu']['charset'] = 'utf8';


$config['mysql']['asyn_max_count'] = 20;
return $config;