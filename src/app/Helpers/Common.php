<?php
/**
 * Created by PhpStorm.
 * User: linmaogan
 * Date: 2017/12/9
 * Time: 下午11:15
 */

//function getWeChatConfigByChannelId($channelId)
//{
//    return get_instance()->config->get('weChat.' . $channelId);
//}

/**
 * 随机生成以指定前缀开头的字符串 长度为length+2
 * @param $length
 * @param string $prefix
 * @return string
 */
function getRandString($length, $prefix = '')
{
    $string = "QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm";
    str_shuffle($string);
    $randString = $prefix . substr(str_shuffle($string), 0, $length);
    return $randString;
}

/*function getChannelConfig($keyValue = '', $keyType = 'channelId')
{
    static $config = [];

    if ($config) {
        return $keyValue ? $config[$keyValue] : $config;
    }

    if ($keyType == 'channelId') {
        $key = 'channelInfoById';
    } else {
        $key = 'channelInfoByTag';
    }
    $configObject = getModuleConfig('common');
    $config = $configObject->get($key);

    return $keyValue ? $config[$keyValue] : $config;
}*/

/**
 * 生成签名
 * @param $params
 * @param $secret
 * @param array $signFields 参数签名的键名
 * @return string
 */
function makeSign($params, $secret, $signFields = [])
{
    if ($signFields) {
        foreach ($params as $key => $value) {
            if (!in_array($key, $signFields)) {
                unset($params[$key]);
            }
        }
    }

    $stringArray = [];
    ksort($params);
    foreach ($params as $key => $value) {
        if ($key == 'sign') {
            continue;
        }
        $stringArray[] = "$key=" . urldecode($value);
    }
    $string = implode('', $stringArray);
    $string .= $secret;

    $sign = md5($string);

//    customLog('Common function makeSign:' . $string . ' sign:' . $sign);

    return $sign;
}

/**
 * 签名验证
 * @param $params
 * @param $secret
 * @param array $signFields 参数签名的键名
 * @return bool
 */
function checkSign($params, $secret, $signFields = [])
{
    $sign = $params['sign'];
    unset($params['sign']);
    if (!$sign) {
        return false;
    }

    if ($signFields) {
        foreach ($params as $key => $value) {
            if (!in_array($key, $signFields)) {
                unset($params[$key]);
            }
        }
    }

    $newSign = makeSign($params, $secret, $signFields);
    if ($sign != $newSign) {
        recordLog([$sign, $newSign, $params, $secret, $signFields], 'checkSign', 'checkSign', LOG_DIR . '/checkSign');
        return false;
    }

    return true;
}

/**
 * 获取模块配置，返回配置对象，而不是数组
 * @param $moduleName
 * @return \Noodlehaus\Config
 */
function getModuleConfig($moduleName)
{
    return new \Noodlehaus\Config(getModuleConfigDir($moduleName));
}

function getModuleConfigDir($moduleName)
{
    $env_SD_CONFIG_DIR = getenv("SD_CONFIG_DIR");
    if (!empty($env_SD_CONFIG_DIR)) {
        $dir = CONFIG_DIR . '/' . $env_SD_CONFIG_DIR . '/' . $moduleName;
    } else {
        $dir = CONFIG_DIR . '/' . $moduleName;
    }
    if (!is_dir($dir)) {
        secho("STA", "$dir 目录不存在\n");
        exit();
    }
    return $dir;
}

/**
 * 参数过滤器
 * @param $params
 * @param $filterList 参数列表，如 ['userName', 'mobile']
 * @return array
 */
function paramsFilter($params, $filterList)
{
    $newParams = [];
    foreach ($filterList as $value) {
        if (isset($params[$value])) {
            $newParams[$value] = $params[$value];
        }
    }

    return $newParams;
}

/**
 * 验证传参
 * @param $rules 例如 ['id' => 'required|integer|email|mobile|url|max:1000|min:2|in:(1,2,3)']
 * @param $params
 * @return bool
 */
function validateParams($rules, $params)
{
    if (!isset($rules) || !isset($params) || !is_array($rules) || !is_array($params)) {
        return false;
    }
    foreach ($rules as $param => $rule) {
        $param = str_replace(' ', '', $param);
        $rule = str_replace(' ', '', $rule);
        $conditions = explode('|', $rule);
        foreach ($conditions as $condition) {
            if (strpos($condition, ':') !== false) {
                list($identifier, $value) = explode(':', $condition);
            } else {
                $identifier = $condition;
            }

            if ($identifier != 'required' && !isset($params[$param])) {
                continue;
            }

            switch ($identifier) {
                case 'required':
                    if (!isset($params[$param]) || $params[$param] === '' || $params[$param] === array()) {
                        return false;
                    }
                    break;
                case 'num':  // 数字
                    if (!is_numeric($params[$param])) {
                        return false;
                    }
                    break;
                case 'integer':  // 整数
                    if (!is_numeric($params[$param])) {
                        return false;
                    }

                    $params[$param] = $params[$param] * 1;
                    $mi = floor($params[$param]);
                    if ($mi != $params[$param]) {
                        return false;
                    }

                    break;
                case 'max':
                    if (!is_numeric($params[$param])) {
                        return false;
                    }

                    $params[$param] = $params[$param] * 1;
                    $value = $value * 1;
                    if ($params[$param] > $value) {
                        return false;
                    }

                    break;
                case 'min':
                    if (!is_numeric($params[$param])) {
                        return false;
                    }

                    $params[$param] = $params[$param] * 1;
                    $value = $value * 1;
                    if ($params[$param] < $value) {
                        return false;
                    }

                    break;
                case 'positive':
                    if (!is_numeric($params[$param])) {
                        return false;
                    }

                    $params[$param] = $params[$param] * 1;
                    if ($params[$param] < 1) {
                        return false;
                    }

                    break;
                case 'email':
                    if (!empty($params[$param]) && filter_var($params[$param], FILTER_VALIDATE_EMAIL) === false) {
                        return false;
                    }
                    break;
                case 'mobile':
                    if (!empty($params[$param]) && !preg_match('/^1[34578]\d{9}$/', $params[$param])) {
                        return false;
                    }
                    break;
                case 'url':
                    if (!empty($params[$param]) && filter_var($params[$param], FILTER_VALIDATE_URL) === false) {
                        return false;
                    }
                    break;
                case 'maxlen':
                    if (!empty($params[$param]) && mb_strlen($params[$param], 'UTF-8') > $value) {
                        return false;
                    }
                    break;
                case 'minlen':
                    if (!empty($params[$param]) && mb_strlen($params[$param], 'UTF-8') < $value) {
                        return false;
                    }
                    break;
                case 'len':
                    if (!empty($params[$param]) && mb_strlen($params[$param], 'UTF-8') != $value) {
                        return false;
                    }
                    break;
                case 'price':
                    if (!is_numeric($params[$param]) || $params[$param] <= 0 || !preg_match('/^(\d)+$|^(\d)+\.\d{1,2}$/', $params[$param])) {
                        return false;
                    }
                    break;
                case 'field_string':
                    if (!is_string($params[$param])) {
                        return false;
                    }
                    $str = str_replace(' ', '', $params[$param]);
                    if (!preg_match('/^[a-zA-Z0-9_(),`=]+$/', $str)) {
                        return false;
                    }
                    break;
                case 'field_array':
                    if (!is_array($params[$param])) {
                        return false;
                    }
                    $str = str_replace(' ', '', implode('', $params[$param]));
                    if (!preg_match('/^[a-zA-Z0-9_()`=]+$/', $str)) {
                        return false;
                    }
                    break;
                case 'in':
                    if (empty($value)) {
                        return false;
                    }
                    $str = str_replace('(', '', $value);
                    $str = str_replace(')', '', $str);
                    $str_array = explode(',', $str);
                    if (!in_array($params[$param], $str_array)) {
                        return false;
                    }
                    break;
            }
        }
    }
    return true;
}

//function getGameConfig($appId)
//{
//    return get_instance()->config->get('game.app.' . $appId);
//}

function customLog($message, $level = \Monolog\Logger::DEBUG)
{
    if (is_array($message)) {
        $message = json_encode($message, JSON_UNESCAPED_UNICODE);
    }
    get_instance()->log->log($level, $message);
}

function getErrorByCode($code, $message = '')
{
    $errorConfig = getModuleConfig('common');
    $codes = $errorConfig->get('errorCode.error');
    $errors = $errorConfig->get('errorCode.code');
    $originalCode = $code;
    $code = isset($codes[$code]) ? $codes[$code] : '510';
    $message = $message != '' ? $message : ($code == 510 ? ($errors[$code] . '：' . $originalCode) : $errors[$code]);
    return ['code' => $code, 'message' => $message];
}

function api($moduleName, $register = true, $objectDir = '', $exit = true)
{
    return app\Library\Core\CoreObject::get($moduleName, $register, $objectDir, $exit);
}

/**
 * 获取通信协议
 * @param $channelId
 * @return string
 */
function getHttpProtocol($channelId)
{
    $httpProtocol = 'https';
    $moduleConfig = getModuleConfig('common');
    if (in_array($channelId, $moduleConfig->get('global.httpProtocol'))) {
        $httpProtocol = 'http';
    }
    return $httpProtocol;
}

/**
 * 返回url中指定的组成部分
 * @param $url 示例：$url = 'http://username:password@hostname/path?arg=value#anchor';
 * @param array $getKey 所有可能值：['scheme', 'host', 'port', 'user', 'pass', 'path', 'query', 'fragment']
 * @return bool|string
 */
function unParseUrl($url, $getKey = ['scheme', 'host', 'port'])
{
    $parsedUrl = parse_url(trim($url));

    $scheme = $user = $pass = $host = $port = $path = $query = $fragment = '';
    if (!$parsedUrl) {
        return false;
    }

    $component = ['scheme', 'host', 'port', 'user', 'pass', 'path', 'query', 'fragment'];
    if ($getKey) {
        $component = array_intersect($getKey, $component);
    }

    foreach ($component as $value) {
        if ($value == 'scheme') {
            $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
        } elseif ($value == 'host') {
            $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
        } elseif ($value == 'port') {
            $port = isset($parsedUrl['port']) ? $parsedUrl['port'] : '';
        } elseif ($value == 'user') {
            $user = isset($parsedUrl['user']) ? $parsedUrl['user'] : '';
        } elseif ($value == 'pass') {
            $pass = isset($parsedUrl['pass']) ? ':' . $parsedUrl['pass'] : '';
            $pass = ($user || $pass) ? "$pass@" : '';
        } elseif ($value == 'path') {
            $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
        } elseif ($value == 'query') {
            $query = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
        } elseif ($value == 'fragment') {
            $fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';
        }
    }

    return "$scheme$user$pass$host$port$path$query$fragment";
}

/**
 * 检查和设置【数组】默认值
 * @param array $data
 * @param sting $key
 * @param string $default
 * @return string
 */
function checkAndSetArray($data, $key, $default = '')
{
    return isset($data[$key]) && $data[$key] ? $data[$key] : $default;
}

function checkAndSetArrayByReference(&$set, $data, $key, $newKey = null, $default = null)
{
    $argsNum = func_num_args();
    $arrayKey = $newKey ? $newKey : $key;
    if (isset($data[$key])) {
        $set[$arrayKey] = $data[$key];
    } elseif ($argsNum == 5) {
        $set[$arrayKey] = $default;
    }
}

/**
 * 检查和设置【字符串】默认值
 * @param string $data
 * @param string $default
 * @return string
 */
function checkAndSetString($data, $default = '')
{
    return $data ? $data : $default;
}

/**
 * 转化特殊字符为 HTML 实体
 * @param string|array $string
 * @param string $flags 位掩码，默认是 ENT_COMPAT | ENT_HTML401。
 * @return array|mixed|string
 */
function convertSpecialChars($string, $flags = null)
{
    if (is_array($string)) {
        foreach ($string as $key => $value) {
            $string[$key] = convertSpecialChars($value);
        }
    } else {
        if ($flags === null) {
            $string = str_replace(array('&', '"', '<', '>'), array('&amp;', '&quot;', '&lt;', '&gt;'), $string);
            if (strpos($string, '&amp;#') !== false) {
                $string = preg_replace('/&amp;((#(\d{3,5}|x[a-fA-F0-9]{4}));)/', '&\\1', $string);
            }
        } else {
            $string = htmlspecialchars($string, $flags, 'UTF-8');
        }
    }
    return $string;
}


/**
 * 防错获取对象属性
 * @param $obj
 * @param $property string
 * @param null $default
 * @return mixed|string
 */
function getProperty($obj, $property, $default = NULL)
{
    if (!$obj) return $default;
    if (is_object($obj)) {
        if (get_class($obj) == 'Server_data_object') {
            $result = $obj->{$property};
            return $result === null ? $default : $result;
        }

        return property_exists($obj, $property) ? $obj->$property : $default;
    }
    return isset($obj[$property]) ? $obj[$property] : $default;
}

/**
 * 二维数组排序
 * @param $arr
 * @param $keys
 * @param string $type
 * @param int $limit
 * @param null $returnKey
 * @return array
 */
function arraySort($arr, $keys, $type = 'desc', $limit = 0, $returnKey = null)
{
    $keysvalue = $new_array = [];
    if (!empty($arr)) {
        foreach ($arr as $k => $v) {
            $keysvalue[$k] = getProperty($v, $keys);
        }
        if ($type == 'asc') {
            asort($keysvalue);
        } else {
            arsort($keysvalue);
        }
        reset($keysvalue);

        $i = 0;
        foreach ($keysvalue as $k => $v) {
            if ($returnKey == null) {
                $new_array[] = $arr[$k];
            } else {
                $new_array[] = getProperty($arr[$k], $returnKey);
            }
            if ($limit > 0) {
                if (++$i >= $limit) {
                    break;
                }
            }
        }
    }
    return $new_array;
}


/**
 * 根据渠道获取前缀(传入userid返回拼接好的，不传直接返回前缀)
 * @param $channelId  int 渠道id
 * @param $user_id int 用户id（可不传）
 * @return mixed|bool
 */
function getUserChannelPrefix($channelId, $user_id = 0)
{
    if ($channelId < 0) {
        return false;
    }
    $moduleConfig = getModuleConfig('common');
    $channelPrefixs = $moduleConfig->get('userChannelPrefix');
    if ($user_id > 0) {
        if (array_key_exists($channelId, $channelPrefixs)) {
            $prefix = $channelPrefixs[$channelId];
            return $prefix . $user_id;
        }
        return false;
    } else {
        if (array_key_exists($channelId, $channelPrefixs)) {
            $prefix = $channelPrefixs[$channelId];
            return $prefix;
        }
        return false;
    }
}

/**
 * 根据带字母的用户id获取渠道id
 * @param $channelId  int 渠道id
 * @param $user_id string
 * @return mixed|bool
 */
function getChannelIdByUserPrefix($user_id = '')
{
    if ($user_id == '') {
        return false;
    }
    $moduleConfig = getModuleConfig('common');
    $channelPrefixs = $moduleConfig->get('userChannelPrefix');
    return array_search(substr($user_id, 0, 2), $channelPrefixs);
}


/**
 * @desc 获取当月第一天和最后一天的时间戳或日期
 * @author Gump <linmaogan@guiwan.net>
 * @update 2017-06-05
 * @access public
 * @param $date 当天日期 date("Y-m-d")
 * @param $type 返回类型 'date'返回日期格式，否则返回时间戳格式
 * @return array 返回当月第一天和最后一天的时间戳和日期
 */
function getMonth($date, $type = '')
{
    date_default_timezone_set('Asia/Shanghai');
    $firstDay = date('Y-m-01', strtotime($date));
    $lastDay = date('Y-m-d', strtotime("$firstDay +1 month -1 day"));
    $firstDayTimeStamp = strtotime($firstDay);
    $lastDayTimeStamp = strtotime($lastDay) + 60 * 60 * 24 - 1; // 加上23:59:59
    $result = $type == 'date' ? array($firstDay, $lastDay) : array($firstDayTimeStamp, $lastDayTimeStamp);
    return $result;
}

/**
 * 二维数组根据字段进行排序
 * @params array $array 需要排序的数组
 * @params string $field 排序的字段
 * @params string $sort 排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
 */
function twoDimensionalArraySort($array, $field, $sort = 'SORT_DESC')
{
    $arrSort = array();
    foreach ($array as $uniqid => $row) {
        foreach ($row as $key => $value) {
            $arrSort[$key][$uniqid] = $value;
        }
    }
    array_multisort($arrSort[$field], constant($sort), $array);
    return $array;
}

/**
 * 提取字符串中的数字
 * @param string $string
 * @return integer
 */
function findNum($string = '')
{
    $string = trim($string);
    if (empty($string)) {
        return '';
    }
    $result = '';
    for ($i = 0; $i < strlen($string); $i++) {
        if (is_numeric($string[$i])) {
            $result .= $string[$i];
        }
    }
    return $result;
}

/**
 * @desc 记录日志
 * @author Gump <linmaogan@gmail.com>
 * @update 2016年9月14日
 * @access public
 * @param string $fileName
 * @param string $message
 * @return null
 */
function recordLog($message, $flag = 'flag', $fileName = 'debug', $dir = '', $simpleLog = FALSE, $isEcho = 0)
{
    $dir = $dir ? $dir : LOG_DIR . '/debug';
    if (createDir($dir)) {
        $fileName = date('Ymd', time()) . '_' . $fileName . '.log';
        $handle = fopen($dir . '/' . $fileName, "a");
        flock($handle, LOCK_EX);
        $pre = date("YmdHis", time()) . ' [' . $flag . '] ';
        if ($simpleLog) {
            $message = $pre . $message . "\n";
            fwrite($handle, $message);
        } else {
            $message = $pre . json_encode($message, JSON_UNESCAPED_UNICODE) . "\n";
            fwrite($handle, $message); // . "\n"
        }

        flock($handle, LOCK_UN);
        fclose($handle);

        if ($isEcho) {
            echo $message . "\n <br />";
            ob_flush();
        }
    } else {
        exit('目录不存在！');
    }
}

/**
 * 根据debug设置是否开启日志记录
 * @param $message
 * @param string $flag
 * @param string $fileName
 * @param string $dir
 * @param bool $simpleLog
 * @param int $isEcho
 */
function recordLogDebug($message, $flag = 'flag', $fileName = 'debug', $dir = '', $simpleLog = FALSE, $isEcho = 0)
{
    if (FP_DEBUG) {
        recordLog($message, '[debug]' . $flag, $fileName, $dir, $simpleLog, $isEcho);
    }
}

/**
 * @desc  创建目录
 * @author Gump <linmaogan@guiwan.net>
 * @update 2017-02-23
 * @access private
 * @param  $dir 要创建的目录路径，可创建多层目录
 * @param int $mode
 * @param bool $recursive
 * @return bool
 */
function createDir($dir, $mode = 0777, $recursive = true)
{
    if (is_null($dir) || $dir === "") {
        return false;
    }
    if (is_dir($dir) || $dir === "/") {
        return true;
    }

    return mkdir($dir, $mode, $recursive);
}

function getCurrentTimeAndMemory()
{
    $microTime = microtime(true);
    $memory = memory_get_usage();
    return ['microTime' => $microTime, 'memory' => $memory];
}

function getUsedTimeAndMemory($startTime, $startMemory)
{
    $microTime = microtime(true);
    $memory = memory_get_usage();
    $usedTime = ($microTime - $startTime) . ' s';
    $usedMemory = ($memory - $startMemory) / 1024 . ' kb'; // 转换为kb
    return ['usedTime' => $usedTime, 'usedMemory' => $usedMemory];
}

/**
 * 新老渠道适配器
 * @param $channelId
 * @return bool
 */
function judgeChannelAdapter($channelId)
{
    $oldChannelIds = [1008];

    // 判断为新渠道
    if (in_array($channelId, $oldChannelIds) || $channelId >= 1043) {
        return true;
    }

    return false;
}

/**
 * 根据数组指定的键对应的值, 作为新数组的键名
 *
 * @param array $arr 二维数组
 * @param string $key 键名
 *
 * @return array
 */
function arrayCombineByKey($arr, $key)
{
    $keys = array_column($arr, $key);
    return array_combine($keys, $arr);
}


/**
 * 测试
 * @param $id
 * @return int
 */
function getId($id)
{
    return $id ?: 1;
}