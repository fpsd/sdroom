<?php
/**
 * Created by PhpStorm.
 * User: zhangjincheng
 * Date: 16-7-14
 * Time: 下午1:58
 */
/**
 * timerTask定时任务
 * （选填）task名称 task_name
 * （选填）model名称 model_name  task或者model必须有一个优先匹配task
 * （必填）执行task的方法 method_name
 * （选填）执行开始时间 start_time,end_time) 格式： Y-m-d H:i:s 没有代表一直执行,一旦end_time设置后会进入1天一轮回的模式
 * （必填）执行间隔 interval_time 单位： 秒
 * （选填）最大执行次数 max_exec，默认不限次数
 * （选填）是否立即执行 delay，默认为false立即执行
 */
$config['timerTask'] = [];
// Task测试示例：在每天的14点到20点间每隔1秒执行一次
/*$config['timerTask'][] = [
//    'start_time' => 'Y-m-d 14:00:00',
//    'end_time' => 'Y-m-d 20:00:00',
    'task_name' => 'Test/Lmg/Test',
    'method_name' => 'test',
    'interval_time' => '1',
];

// Model测试示例：在每天的14点到20点间每隔1秒执行一次
$config['timerTask'][] = [
//    'start_time' => 'Y-m-d 14:00:00',
//    'end_time' => 'Y-m-d 20:00:00',
    'model_name' => 'Task/Test/Lmg/Test',
    'method_name' => 'test',
    'interval_time' => '60',
];*/

//下面例子表示在每天的14点到20点间每隔1秒执行一次
/*$config['timerTask'][] = [
    //'start_time' => 'Y-m-d 19:00:00',
    //'end_time' => 'Y-m-d 20:00:00',
    'task_name' => 'TestTask',
    'method_name' => 'test',
    'interval_time' => '1',
];*/
//下面例子表示在每天的14点到15点间每隔1秒执行一次，一共执行5次
/*$config['timerTask'][] = [
    'start_time' => 'Y-m-d 14:00:00',
    'end_time' => 'Y-m-d 15:00:00',
    'task_name' => 'TestTask',
    'method_name' => 'test',
    'interval_time' => '1',
    'max_exec' => 5,
];*/
//下面例子表示在每天的0点执行1次(间隔86400秒为1天)
/*$config['timerTask'][] = [
    'start_time' => 'Y-m-d 23:59:59',
    'task_name' => 'TestTask',
    'method_name' => 'test',
    'interval_time' => '86400',
];*/
//下面例子表示在每天的0点执行1次
/*$config['timerTask'][] = [
    'start_time' => 'Y-m-d 14:53:10',
    'end_time' => 'Y-m-d 14:54:11',
    'task_name' => 'TestTask',
    'method_name' => 'test',
    'interval_time' => '1',
    'max_exec' => 1,
];*/

//返利0点进行返利汇总
/*$config['timerTask'][] = [
    'start_time' => 'Y-m-d 23:59:59',
    'task_name' => 'RebateStatisticByDay/RebateStatisticByDay',
    'method_name' => 'rebateStatisticByDay',
    'interval_time' => '86400',
];

//自动确认收货   每小时执行一次
$config['timerTask'][] = [
    'start_time' => 'Y-m-d 00:00:00',
    'end_time' => 'Y-m-d 23:59:59',
    'task_name' => 'Auto/AutoReceipt',
    'method_name' => 'autoReceipt',
    'interval_time' => '3600',
];
//自动超时未支付取消订单  线下商品--兑奖   每1分钟执行一次
$config['timerTask'][] = [
    'start_time' => 'Y-m-d 00:00:00',
    'end_time' => 'Y-m-d 24:00:00',
    'task_name' => 'Hall/TimeoutPayment',
    'method_name' => 'timeoutPayment',
    'interval_time' => '60',
];
//自动超时未支付取消订单  线上商品--物流   每1分钟执行一次
$config['timerTask'][] = [
    'start_time' => 'Y-m-d 00:00:00',
    'end_time' => 'Y-m-d 24:00:00',
    'task_name' => 'Hall/TimeoutPaymentUpGoods',
    'method_name' => 'timeoutPaymentUpGoods',
    'interval_time' => '60',
];
// 上报消耗返利：每秒执行一次
//$config['timerTask'][] = [
//    'start_time' => 'Y-m-d 00:00:00',
//    'end_time' => 'Y-m-d 20:00:00',
//    'task_name' => 'RebateTopic/RebateTopic',
//    'method_name' => 'RebateTopic',
//    'interval_time' => '1',
//];

// 商品自动上下架，每分钟执行一次
/*$config['timerTask'][] = [
    'task_name' => 'StoreGoods/StoreGoodsUpDownShelf',
    'method_name' => 'storeGoodsUpDownShelf',
    'interval_time' => '60',
];*/

// 汇总商户每天可提现多少钱，每天零点执行一次
/*$config['timerTask'][] = [
    'start_time' => 'Y-m-d 23:59:59',
    'task_name' => 'Settlement/StoreInfoMoneyStatisticsDay',
    'method_name' => 'storeInfoMoneyStatisticsDay',
    'interval_time' => '86400',
];*/

//汇总商品的截止昨天的相关数据信息-store_goods_statistic 表
/*$config['timerTask'][] = [
    'start_time' => 'Y-m-d 24:00:00',
    'task_name' => 'StoreGoods/StoreGoodsStatistic',
    'method_name' => 'storeGoodsStatistic',
    'interval_time' => '86400',
];
//汇总当天下架商品7天的平均交易额
$config['timerTask'][] = [
    'start_time' => 'Y-m-d 24:00:00',
    'task_name' => 'StoreGoods/StoreGoodsOffSevenDay',
    'method_name' => 'storeGoodsOffSevenDay',
    'interval_time' => '86400',
];
//已支付订单过期状态的自动更新
$config['timerTask'][] = [
    'task_name' => 'StoreGoods/StoreGoodsCodeStatusInvalid',
    'method_name' => 'storeGoodsCodeStatusInvalid',
    'interval_time' => '60',
];
//每周四生成需要向商户打款的订单集合:待确认时间性
$config['timerTask'][] = [
    'start_time' => 'Thursday',
    'task_name' => 'Settlement/StoreOrderOnThursday',
    'method_name' => 'storeOrderOnThursday',
    'interval_time' => '86400',
    'max_exec' => '1',
];
// 棋牌完成六局游戏奖励房卡，每60s执行一次
//$config['timerTask'][] = [
////    'start_time' => 'Y-m-d 23:59:59',
//    'task_name' => 'Agent/QipaiInviteUser',
//    'method_name' => 'qipaiInviteUser',
//    'interval_time' => '60',
//];
*/


return $config;