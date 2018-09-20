<?php
/**
 * email: 68527761@qq.com
 * User : chenqianhao
 * Name : Iappay.php
 * Date : 2018/4/13
 * Time : 上午11:20
 * Desc : 爱贝充值基类
 */

namespace app\Library\Pay\Iappay;

use app\Library\Core\BaseObject;

// use app\BaseException;

class Iappay extends BaseObject
{
    public function __construct()
    {
        parent::__construct();
    }

    public function initialization(&$context)
    {
        parent::initialization($context);
    }

    public function setConfig($channelId = 0)
    {
        $config = $this->commonConfig->get('iappay.default'); // 爱贝支付的默认配置文件
        $channelConfig = $this->commonConfig->get('iappay.' . $channelId); // 爱贝支付的配置文件
        if ($channelConfig) {
            $config = array_merge($config, $channelConfig);
        }
        return $config;
    }

    /**格式化公钥
     * $pubKey PKCS#1格式的公钥串
     * return pem格式公钥， 可以保存为.pem文件
     */
    public function formatPubKey($pubKey)
    {
        $fKey = "-----BEGIN PUBLIC KEY-----\n";
        $len = strlen($pubKey);
        for ($i = 0; $i < $len;) {
            $fKey = $fKey . substr($pubKey, $i, 64) . "\n";
            $i += 64;
        }
        $fKey .= "-----END PUBLIC KEY-----";
        return $fKey;
    }


    /**格式化公钥
     * $priKey PKCS#1格式的私钥串
     * return pem格式私钥， 可以保存为.pem文件
     */
    public function formatPriKey($priKey)
    {
        $fKey = "-----BEGIN RSA PRIVATE KEY-----\n";
        $len = strlen($priKey);
        for ($i = 0; $i < $len;) {
            $fKey = $fKey . substr($priKey, $i, 64) . "\n";
            $i += 64;
        }
        $fKey .= "-----END RSA PRIVATE KEY-----";
        return $fKey;
    }

    /**RSA签名
     * $data待签名数据
     * $priKey商户私钥
     * 签名用商户私钥
     * 使用MD5摘要算法
     * 最后的签名，需要用base64编码
     * return Sign签名
     */
    public function sign($data, $priKey)
    {
        //转换为openssl密钥
        $res = openssl_get_privatekey($priKey);

        //调用openssl内置签名方法，生成签名$sign
        openssl_sign($data, $sign, $res, OPENSSL_ALGO_MD5);

        //释放资源
        openssl_free_key($res);

        //base64编码
        $sign = base64_encode($sign);
        return $sign;
    }

    /**RSA验签
     * $data待签名数据
     * $sign需要验签的签名
     * $pubKey爱贝公钥
     * 验签用爱贝公钥，摘要算法为MD5
     * return 验签是否通过 bool值
     */
    public function verify($data, $sign, $pubKey)
    {
        //转换为openssl格式密钥
        $res = openssl_get_publickey($pubKey);

        //调用openssl内置方法验签，返回bool值
        $result = (bool)openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_MD5);

        //释放资源
        openssl_free_key($res);

        //返回资源是否成功
        return $result;
    }


    /**
     * 解析response报文
     * $content  收到的response报文
     * $pkey     爱贝平台公钥，用于验签
     * $respJson 返回解析后的json报文
     * return    解析成功TRUE，失败FALSE
     */
    public function parseResp($content, $pkey, &$respJson)
    {
        // $arr = array_map(create_function('$v', 'return explode("=", $v);'), explode('&', $content));

        $createf = function ($v) {
            return explode("=", $v);
        };
        $arr = array_map($createf, explode('&', $content));

        // $arr = array_map("createf", explode('&', $content));
        foreach ($arr as $value) {
            $resp[($value[0])] = $value[1];
        }

        //解析transdata
        if (array_key_exists("transdata", $resp)) {
            $respJson = json_decode($resp["transdata"]);
        } else {
            return FALSE;
        }

        //验证签名，失败应答报文没有sign，跳过验
        if (array_key_exists("sign", $resp)) {
            //校验签名
            $pkey = $this->formatPubKey($pkey);
            return $this->verify($resp["transdata"], $resp["sign"], $pkey);
        } else if (array_key_exists("errmsg", $respJson)) {
            return FALSE;
        }

        return TRUE;
    }


    /**
     * curl方式发送post报文
     * $remoteServer 请求地址
     * $postData post报文内容
     * $userAgent用户属性
     * return 返回报文
     */
    public function request_by_curl($remoteServer, $postData, $userAgent)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $remoteServer);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        $data = urldecode(curl_exec($ch));
        curl_close($ch);

        return $data;
    }


    /**
     * 组装request报文
     * $reqJson 需要组装的json报文
     * $vkey  cp私钥，格式化之前的私钥
     * return 返回组装后的报文
     */
    public function composeReq($reqJson, $vkey)
    {
        //获取待签名字符串
        $content = json_encode($reqJson);
        //格式化key，建议将格式化后的key保存，直接调用
        $vkey = $this->formatPriKey($vkey);

        //生成签名
        $sign = $this->sign($content, $vkey);

        //组装请求报文，目前签名方式只支持RSA这一种
        $reqData = "transdata=" . urlencode($content) . "&sign=" . urlencode($sign) . "&signtype=RSA";

        return $reqData;
    }

    //H5请求报文拼接
    public function h5composeReq($reqJson, $vkey)
    {
        //获取待签名字符串
        $content = json_encode($reqJson);
        //格式化key，建议将格式化后的key保存，直接调用
        $vkey = $this->formatPriKey($vkey);

        //生成签名
        $sign = $this->sign($content, $vkey);

        //组装请求报文，目前签名方式只支持RSA这一种
        $reqData = "data=" . urlencode($content) . "&sign=" . urlencode($sign) . "&sign_type=RSA";

        return $reqData;
    }

    //发送post请求 ，并得到响应数据  和对数据进行验签
    public function HttpPost($Url, $reqData)
    {
        global $cpvkey, $platpkey;
        $respData = $this->request_by_curl($Url, $reqData, " demo ");
        if (!$this->parseResp($respData, $platpkey, $notifyJson)) {
            echo "fail";
        }
        echo "TEST respData:$respData\n";
    }
}