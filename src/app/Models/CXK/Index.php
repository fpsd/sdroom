<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/24
 * Time: 14:49
 */

namespace app\Models\CXK;


use app\Models\BaseModel;

class Index extends BaseModel
{
    public function index($a)
    {
        $ret = yield $this->mysql_pool->dbQueryBuilder
            ->select('*')
            ->from('prize_grant_log')
            ->coroutineSend();
        return $ret;
    }
}