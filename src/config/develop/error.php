<?php
//错误收集上报系统
$config['error']['enable'] = true;
//是否显示在http上
$config['error']['http_show'] = true;
//访问地址，需自己设置ip：port
$config['error']['url'] = "http://api.fpwan.me:8081/Error";
$config['error']['redis_prefix'] = '@sd-error_';
$config['error']['redis_timeOut'] = '36000';

$config['error']['dingding_enable'] = true;
$config['error']['dingding_url'] = 'https://oapi.dingtalk.com';
//钉钉机器人，需自己申请
$config['error']['dingding_robot'] = '/robot/send?access_token=c2fef80df06c8dea4b075b7198ccbe7a59a7724a8e6efd78ef47ce5a279165ca12';
return $config;