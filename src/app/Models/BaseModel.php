<?php
/**
 * Created by FPHD.COM.
 * User: linmaogan
 * Date: 2017/12/7
 * Time: 上午1:01
 */

namespace app\Models;

use Monolog\Logger;
use app\BaseException;
use Server\CoreBase\Model;

class BaseModel extends Model
{
    protected $mysqlPool;
    protected $redisPool;
    protected $commonConfig;

    public function __construct()
    {
        parent::__construct();

        // 设置数据库连接池
        $this->setDatabaseAsynPool();
        $this->commonConfig = getModuleConfig('common');
    }

    public function initialization(&$context)
    {
        parent::initialization($context);

        // 安装MySql连接池
        $this->installMysqlAsynPool();
    }

    /**
     * 判断mysql是否有值
     * @param $result
     * @param $message
     * @throws BaseException
     */
    public function judgeMysqlHaveValue($result,$message)
    {
        if (count($result['result']) == 0) {
            throw new BaseException($message);
        }
    }

    /**
     * @desc 过滤结果中多余的字段
     * @param array $data 可以是一维数组，也可以是二维数组
     * @param array $fields
     * @return array
     */
    public function filterResultField($data, $fields)
    {
        if (!$data || !$fields || !is_array($data) || !is_array($fields)) {
            return $data; // 参数有问题时原值返回
        }

        $result = [];
        $firstArray = reset($data);
        if (is_array($firstArray)) {
            foreach ($data as $key => $value) {
                $result[$key] = array_intersect_key($value, array_flip($fields));
            }
        } else {
            $result = array_intersect_key($data, array_flip($fields));
        }
        return $result;
    }

    public function checkAndSet(&$set, $data, $key, $newKey = null, $default = null)
    {
        $argsNum = func_num_args();
        $arrayKey = $newKey ? $newKey : $key;
        if (isset($data[$key]))
        {
            $set[$arrayKey] = $data[$key];
        } elseif ($argsNum == 5) {
            $set[$arrayKey] = $default;
        }
    }

    public function setDefaultValue($data, $key, $default = null)
    {
        $argsNum = func_num_args();
        if (isset($data[$key]))
        {
            return $data[$key];
        } elseif ($argsNum == 3) {
            return $default;
        }
    }

    public function setTableValue($data, $tableFields)
    {
        if (!$data || !$tableFields) {
            return false;
        }

        $fields = array_keys($data);
        if (!$fields) {
            return false;
        }

        $setFields = array_intersect($tableFields, $fields);
        if (!$setFields) {
            return false;
        }

        $set = [];
        foreach ($setFields as $keyValue) {
            $this->checkAndSet($set, $data, $keyValue);
        }
        return $set;
    }

    /**
     * 打印日志
     * @param string || array $message
     * @param int $level
     */
    protected function log($message, $level = Logger::DEBUG)
    {
        try {
            if (is_array($message)) {
                $message = json_encode($message, JSON_UNESCAPED_UNICODE);
            }

            $this->logger->addRecord($level, $message, $this->getContext());
        } catch (\Exception $e) {

        }
    }

    protected function getTableByModulo($tablePrefix, $value1, $value2)
    {
        return $tablePrefix . ($value1 % $value2);
//        return $tablePrefix . 299; // 测试时不分表
    }

    protected function getTableByMonth($tablePrefix, $time = 0)
    {
        !$time && $time = time();
        return $tablePrefix . (date('Ym', $time));
    }

    private function setDatabaseAsynPool()
    {
        // 设置MySql异步连接池
        if ($this->config->get('mysql.enable', true)) {
            $mySqlConfig = $this->config->get('mysql');
            foreach ($mySqlConfig as $key => $value) {
                $otherKey = ['enable', 'active']; // 不是具体数据库配置的key
                if (!in_array($key, $otherKey) && $value['enable']) {
                    $this->mysqlPool[$key] = get_instance()->getAsynPool('mysqlPool_' . $key);
                }
            }
        }

        // 设置Redis异步连接池
        if ($this->config->get('redis.enable', true)) {
            $redisConfig = $this->config->get('redis');
            foreach ($redisConfig as $key => $value) {
                $otherKey = ['enable', 'active']; // 不是具体数据库配置的key
                if (!in_array($key, $otherKey) && $value['enable']) {
                    $this->redisPool[$key] = get_instance()->getAsynPool('redisPool_' . $key);
                }
            }
        }
    }

    private function installMysqlAsynPool()
    {
        // 安装MySql异步连接池
        if ($this->config->get('mysql.enable', true)) {
            $mySqlConfig = $this->config->get('mysql');
            foreach ($mySqlConfig as $key => $value) {
                $otherKey = ['enable', 'active']; // 不是具体数据库配置的key
                if (!in_array($key, $otherKey) && $value['enable']) {
                    if ($this->mysqlPool[$key] != null) {
                        $this->installMysqlPool($this->mysqlPool[$key]);
                    }
                }
            }
        }
    }

    /**
     * 构建mysql条件
     * 数组查询   都是and连接
     * @param $mysqlPool
     * @param $whereData
     * @param string $operator
     * @return \Generator     // mysqlWhereArr
     */
    public function createMysqlWhere(&$mysqlPool, $whereData, $operator = '='){
        if(!is_array($whereData)){
            throw new \Exception('参数错误');
        }
        foreach($whereData as $key => $item){
            if(empty($key)){
                continue;
            }
            $keyArr = preg_split('/\s+/', $key);
            $column = trim($keyArr[0]);
            $value = trim($item);
            isset($keyArr[1]) && $operator = $keyArr[1];
            $mysqlPool->where($column, $value, $operator);
        }
    }

    /**
     * 去掉表格前缀
     * 一维数组 或者 二维数组 key格式化输出
     * @param $outputData
     * @param string $delPrefix   //mangeOutput
     * @return array
     */
    public function formatTableField($outputData, $delPrefix = '')
    {
        if(empty($outputData)){
            return $outputData;
        }
        $newOutput = [];
        foreach ($outputData as $key => $item){
            if(!is_array($item)){
                $newOutput[self::nameUpdate($key, $delPrefix)] = $item;
                continue;
            }
            $newOutput[$key] = [];
            foreach ($item as $vkey=> $var){
                $newOutput[$key][self::nameUpdate($vkey, $delPrefix)] = $var;
            }
        }
        return $newOutput;
    }

    private function nameUpdate($name, $delPrefix)
    {
        $name = preg_replace('/^'.$delPrefix.'_?/', '', $name);
        if(strpos($name, '_') === false){
            return $name;
        }
        $name = explode("_", $name);
        $newName = '';
        foreach ($name as $key => $item){
            $newName .= $key ? ucfirst(strtolower($item)) : $item;
        }
        return $newName;
    }
}