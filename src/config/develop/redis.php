<?php
/**
 * Created by PhpStorm.
 * User: zhangjincheng
 * Date: 16-7-14
 * Time: 下午1:58
 */

/**
 * 选择数据库环境
 */
$config['redis']['enable'] = true;

$config['redis']['active'] = 'temp';

/**
 * 临时缓存：数据可丢失，数据丢失对业务没什么大影响
 */
$config['redis']['temp']['enable'] = true;
$config['redis']['temp']['ip'] = 'redis';
$config['redis']['temp']['port'] = 6379;
$config['redis']['temp']['select'] = 1;
$config['redis']['temp']['password'] = '';

/**
 * 长期缓存：数据不可丢失，数据丢失对业务有大影响
 */
$config['redis']['long']['enable'] = true;
$config['redis']['long']['ip'] = 'redis';
$config['redis']['long']['port'] = 6379;
$config['redis']['long']['select'] = 1;
$config['redis']['long']['password'] = '';

$config['redis']['asyn_max_count'] = 10;

/**
 * 最终的返回，固定写这里
 */
return $config;
