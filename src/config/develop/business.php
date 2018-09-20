<?php
/**
 * Created by PhpStorm.
 * User: zhangjincheng
 * Date: 16-7-14
 * Time: 下午1:58
 */

//强制关闭gzip
$config['http']['gzip_off'] = false;

//默认访问的页面
$config['http']['index'] = 'index.html';

/**
 * 设置域名和Root之间的映射关系
 */

$config['http']['root'] = [
    'default' => // 默认站点，负载均衡监控页面
        [
            'root' => 'www',
            'index' => 'Index.html'
        ],
    'hall.fpwan.com' =>  // 大厅
        [
            'root' => 'www',
            'index' => 'Index.html'
        ],
    'api.fpwan.com' =>  // 外部API接口
        [
            'root' => 'www',
            'index' => 'Index.html'
        ],
    'api.fpwan.net' =>  // 内部API接口
        [
            'root' => 'www',
            'index' => 'Index.html'
        ],
    'pay.fpwan.net' =>  // 外部支付接口
        [
            'root' => 'www',
            'index' => 'Index.html'
        ],
    'api.fpwan.me' => // 本地开发测试
        [
            'root' => 'www',
            'index' => 'Index.html'
        ],
    'wx.3mu.me' => // 本地公众号开发测试
        [
            'root' => 'www',
            'index' => 'Index.html'
        ],
];

return $config;
