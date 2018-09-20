<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/4
 * Time: 10:25
 */

namespace app\Service\Kefu;


class Kefu_mqtt extends Kefu
{
    public function mqtt($workerId)
    {
        if ($workerId == 0) {
            $mqtt = new MQTT('tcp://127.0.0.1:1883/', 'root1');
            //设置持久会话
            $mqtt->setConnectClean(false);
            //认证
            $mqtt->setAuth('root1', 'root');
            //存活时间
            $mqtt->setKeepalive(3600);
            //回调
            $mqtt->on('publish', function ($mqtt, PUBLISH $publish_object) {
                printf(
                    "\e[32mI got a message\e[0m:(msgid=%d, QoS=%d, dup=%d, topic=%s) \e[32m%s\e[0m\n",
                    $publish_object->getMsgID(),
                    $publish_object->getQos(),
                    $publish_object->getDup(),
                    $publish_object->getTopic(),
                    $publish_object->getMessage()
                );
            });
            $mqtt->on('connack', function (MQTT $mqtt, CONNACK $connack_object) {
                var_dump("MQTT连接成功");
                $topics['$SYS/#'] = 1;
                $mqtt->subscribe($topics);
            });
            $mqtt->connect();
        }
    }
}