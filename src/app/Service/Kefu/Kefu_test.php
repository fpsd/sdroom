<?php
/**
 * Created by PhpStorm.
 * User: liudou
 * Date: 2018/4/22
 * Time: 10:52
 * Desc: 省份ID 查询当前省份所有渠道ID
 */

namespace app\Service\Kefu;

class Kefu_test extends Kefu
{
    public function initialization(&$context)
    {
        parent::initialization($context);
    }

    public function test()
    {
        $result = yield $this->mysql_pool->dbQueryBuilder->select('*')
            ->from('prize_grant_log')
            ->coroutineSend();
        return $result;
    }
}