<?php

namespace app\Library\Core;

use app\BaseException;

class CoreObject
{
    /**
     * 对象创建器
     * @param $objName
     * @param bool $reg
     * @param string $objDir
     * @param bool $exit
     * @return bool|mixed
     * @throws BaseException
     */
    public static function get($objName, $reg = true, $objDir = '', $exit = true)
    {
        static $objReg = array();
        $originalObjectName = $objName;

        // 注册模式，对于对象而言，每个对象本身可以是单键的。因此可以考虑使用注册模式来去掉没有必要的重复实力话。
        if ($reg && isset($objReg[$originalObjectName])) {
            return $objReg[$originalObjectName];
        }

        // 如果没有手动指定object所在目录，则加载配置文件中的object所在目录
        $objDir = $objDir ? $objDir : CORE_SERVICE_DIR;
        if (!@file_exists($objDir)) {
            if ($exit) {
                self::error('ERROR: You must define obj dir first.');
            } else {
                return false;
            }
        }

        // 对象路由
        $objArr = explode('.', $objName);
        $thePath = '';
        foreach ($objArr as $value) {
            $thePath .= '/' . $value;

            // 判断对象是否存在
            $objPath = $objDir . $thePath . '/' . $value . '.php';
//            throw new BaseException(var_dump(__NAMESPACE__,$objPath));
            if (!@file_exists($objPath)) {
                if ($exit) {
                    self::error('ERROR: The obj path of "' . $thePath . '" is not exists');
                } else {
                    return false;
                }
            }
            //　加载对象
//			require_once $objPath;
//            var_dump($objPath);
        }

        $namespacePrefix = 'app\Service';
        // app\Server\User\Pay\Pay
        $objName = $namespacePrefix . '\\' . implode('\\', $objArr) . '\\' . end($objArr); // 转成命名空间格式

        // 创建对象
        if (!class_exists($objName)) {
            self::error('ERROR: The obj "' . $objName . '" is not exists');
//            return false;
        }
        $obj = new $objName();
//        throw new BaseException(var_dump($objName,$obj));
        // 设置对象的存放目录
        $obj->objDir = $objDir;

        // 判断是否注册该对象
        if ($reg) {
            $objReg[$originalObjectName] = $obj;
        }
        return $obj;
    }

    private static function error($msg)
    {
        $debug = debug_backtrace();
        echo $msg;
        echo "<br>\nfile: {$debug[1]['file']}";
        echo "<br>\nline: {$debug[1]['line']}\n";
        throw new BaseException($msg);
    }
}