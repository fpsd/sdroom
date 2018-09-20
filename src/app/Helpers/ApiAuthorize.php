<?php
/**
 * 与老平台对接的授权接口
 * Created by PhpStorm.
 * User: linmaogan
 * Date: 2017/12/9
 * Time: 下午11:15
 */

namespace app\Helpers;

use app\BaseException;

class ApiAuthorize
{
    /**
     * 创建访问口令
     * @param $userId
     * @param $channelId
     * @return string
     */
    public static function makeToken($userId, $channelId, $apiSecret, $userSecret)
    {
        $timestamp = time();
        $secretUserId = self::makeUserId($userId, $channelId, $userSecret);
        $authorizeKey = md5($apiSecret . $timestamp . $secretUserId);
        $token = $userId . '.' . $timestamp . '.' . md5(implode('.', array($userId, $timestamp, $authorizeKey)));

        $token = self::authorizeCode($token, $apiSecret);
        $token = self::base64UrlEncode($token);
        return $token;
    }

    /**
     * 通过token获取用户userId
     * @param $token
     * @param $channelId
     * @param int $exprired
     * @return int
     * @throws BaseException
     */
    public static function getUserIdByToken($token, $channelId, $apiSecret, $userSecret, $exprired = 0)
    {
        if (!$token || !$channelId) {
            $message = 'Error.Invalid_params';
            $logMessage = [$message, __METHOD__, $token, $channelId];
            throw new BaseException($message, -1, null, $logMessage, \Monolog\Logger::WARNING);
        }

        $originToken = $token;
        $token = self::authorizeCode(self::base64UrlDecode($token), $apiSecret);

        if (!$token || (strpos($token, '.') === false) || count(explode('.', $token)) != 3) {
            $message = 'Error.Token_decryption_failed';
            $logMessage = [$message, __METHOD__, $apiSecret, $originToken, $token, $channelId];
            throw new BaseException($message, -1, null, $logMessage, \Monolog\Logger::WARNING);
        }

        list($userId, $timestamp, $sign) = explode('.', $token);

        $secretUserId = self::makeUserId($userId, $channelId, $userSecret);
        $authorizeKey = md5($apiSecret . $timestamp . $secretUserId);
        $checkSign = md5(implode('.', array($userId, $timestamp, $authorizeKey)));

        if ($sign != $checkSign) {
            $message = 'Error.Invalid_sign';
            $logMessage = [$message, __METHOD__, $token, $channelId, $sign, $checkSign];
            throw new BaseException($message, -1, null, $logMessage, \Monolog\Logger::WARNING);
        }

        // 有效期处理
        if ($exprired && time() - $timestamp > $exprired) {
            $message = 'Error.Token_has_expired';
            $logMessage = [$message, __METHOD__, time() - $timestamp, $exprired];
            throw new BaseException($message, -1, null, $logMessage, \Monolog\Logger::NOTICE);
        }

        return $userId;
    }

    /**
     * 对userId加密
     * @param $userId
     * @param $channelId
     * @return string
     */
    public static function makeUserId($userId, $channelId, $userSecret)
    {
        $secretUserId = self::authorizeCode($userId, $userSecret);
        $secretUserId = self::base64UrlEncode($secretUserId);
        return $secretUserId;
    }

    /**
     * 对userId解密
     * @param $secretUserId
     * @param $channelId
     * @return string
     */
    public static function parseUserId($secretUserId, $channelId, $userSecret)
    {
        $secretUserId = self::base64UrlDecode($secretUserId);
        $userId = self::authorizeCode($secretUserId, $userSecret);
        return $userId;
    }

    // url传输需要替换部分字符
    public static function base64UrlEncode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    // url传输需要替换部分字符
    public static function base64UrlDecode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    /**
     * 加解密函数
     * @param string $input 必须为字符串格式，不能是整型
     * @param $key
     * @return string
     */
    public static function authorizeCode(string $input, $key)
    {
        # Input must be of even length.
        if (strlen($input) % 2) {
            //$input .= '0';
        }

        # Keys longer than the input will be truncated.
        if (strlen($key) > strlen($input)) {
            $key = substr($key, 0, strlen($input));
        }

        # Keys shorter than the input will be padded.
        if (strlen($key) < strlen($input)) {
            $key = str_pad($key, strlen($input), '0', STR_PAD_RIGHT);
        }

        # Now the key and input are the same length.
        # Zero is used for any trailing padding required.
        # Simple XOR'ing, each input byte with each key byte.
        $result = '';
        for ($i = 0; $i < strlen($input); $i++) {
            $result .= $input{$i} ^ $key{$i};
        }

        return $result;
    }
}