<?php

namespace app\Library\Core;

use app\BaseException;
use app\Models\BaseModel;

abstract class BaseObject extends BaseModel
{
    /**
     * 对象的存放目录
     *
     * @var string
     */
    public $objDir;

    /**
     * 自动调用未定义函数
     *
     * @param string $method
     * @param mix $param
     * @return mix
     */
    public function __call($method, $param)
    {
        static $methodReg = array();

        // 读取当前对象名称
        $className = get_class($this);
        $regLabel = $className . '_' . $method;
//        throw new BaseException(var_dump($className, $method,$regLabel));
        // 解决重复调用的问题。如果重复调用同一个方法，则不需用重新路由
        isset($methodReg[$regLabel]) && $funcClassName = $methodReg[$regLabel];
        if (!isset($funcClassName) || !$funcClassName) {

            // 自动寻路，查找需要调用的函数
            $funcPath = self::_getFuncPath($className, $method);
//            throw new BaseException(var_dump($className,$method, $funcPath, $regLabel));
            if (!$funcPath) {
                $this->error("The method file of '{$method}' in '{$className}_{$method}' is not exists");
            }

            // 加载函数对象的路径
            /*require_once $this->objDir . '/' . $funcPath . '.php';
            throw new BaseException(var_dump($className,$method, $param, $this->objDir . '/' . $funcPath . '.php'));
            // 将函数所在的路径转换为函数名
            $pathArr = explode('/', $funcPath);
            array_pop($pathArr);
            $funcClassName = join('_', $pathArr) . '_' . $method;*/
            $funcClassName = $funcPath;

            $methodReg[$regLabel] = $funcClassName;
        }
//        $funcClassName = '\app\Object\User\Pay\Pay_createOrder';
//        throw new BaseException(var_dump(class_exists($funcClassName), $funcClassName));
        // 创建对象
        if (!class_exists($funcClassName)) {
            $this->error("The method of '{$funcClassName}' in '{$className}' is undefine");
        }
        $obj = new $funcClassName();
//        throw new BaseException(var_dump(class_exists($funcClassName), $obj));
        $obj->OBJ = $this;

        // 调用对象的方法
        $result = call_user_func_array(array(&$obj, $method), $param);
        unset($obj);
        return $result;
    }

    /*public function checkMethodExists($method) {
        static $methodReg = array();

        // 读取当前对象名称
        $className = get_class($this);
        $regLabel = $className . '_' . $method;
        if (method_exists($this, $method)) {
            return true;
        }

        if (isset($methodReg[$regLabel])) {
            return true;
        }

        // 自动寻路，查找需要调用的函数
        if (self::_getFuncPath($className, $method)) {
            $methodReg[$regLabel] = true;
            return true;
        } else {
            return false;
        }
    }*/

    /**
     * 读取对象方法所在路径
     * @param $objName
     * @param $method
     * @return bool|string
     */
    private function _getFuncPath($objName, $method)
    {
        if (!$objName) {
            return false;
        }

        $originalObjectName = $objName;

        // 删除命名空间中的路径
        $namespacePrefix = 'app\Service';
        $objName = str_replace($namespacePrefix, '', $objName);

        // 加载对象所在目录
        $objArr = explode('\\', $objName);
        $objArr = array_filter($objArr);

        $prefix = array_pop($objArr);
        $objPath = join('/', $objArr) . '/' . $prefix . '_' . $method;
//        throw new BaseException(var_dump(__NAMESPACE__,$objName,$objArr,$this->objDir,$objPath,$this->objDir . '/' . $objPath . '.php'));
        // 如果当前对象不存在该方法，则搜索父对象
//        var_dump($originalObjectName,$this->objDir . '/' . $objPath . '.php');
        if (!@file_exists($this->objDir . '/' . $objPath . '.php')) {

            $pathArray = explode('\\', $originalObjectName);
            $pathArray = array_filter($pathArray);
            array_pop($pathArray); // 删除末尾两个元数
            array_pop($pathArray);
            $objName = count($pathArray) > 0 ? join('\\', array_merge($pathArray, [end($pathArray)])) : ''; // 补充前缀
//            throw new BaseException(var_dump($originalObjectName, $pathArray,$objName, $method, $this->_getFuncPath($objName, $method)));
            return $this->_getFuncPath($objName, $method);
        } else {
//            throw new BaseException(var_dump($objName, $method));
            return $originalObjectName . '_' . $method;
        }
    }

    /**
     * 错误处理函数
     * @param $msg
     * @throws BaseException
     */
    protected function error($msg)
    {
        throw new BaseException($msg, 1000);
    }
}