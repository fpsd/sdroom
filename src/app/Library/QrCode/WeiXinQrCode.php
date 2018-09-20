<?php
/**
 * email: 68527761@qq.com
 * User : chenqianhao
 * Name : QrCode.php
 * Date : 2018/4/18
 * Time : 下午12:55
 * Desc : 微信二维码生成
 */

namespace app\Library\QrCode;

class WeiXinQrCode
{

    /**
     * 参考文档：https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1443433542 账号管理->生成带参数的二维码
     * 二维码场景值ID，scene_id参数
     * @var int
     */
    private $id;

    /**
     * 保存生成的二维码ticket
     * @var string
     */
    private $ticket;

    /**
     * 公众号的接口凭据
     * @var string
     */
    private $accessToken;

    public function setId($id)
    {
        $this->id = $id;
    }

    public function scene_str($str)
    {
        $this->scene_str = $str;
    }


    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * 获取二维码ticket
     * @return string
     */
    public function getTicket()
    {
        if (null != $this->ticket)
            return $this->ticket;

        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $this->accessToken;
        $data = [
            // 'expire_seconds' => 2592000,//30天，临时二维码最长有效时间
            'action_name' => 'QR_LIMIT_STR_SCENE',//临时型二维码
            'action_info' => [
                'scene' => [
                    // 'scene_id' => $this->id
                    'scene_str' => $this->scene_str
                ]
            ]
        ];
        $obj = self::post($url, $data);
        return $obj;
        // $this->ticket = $obj->ticket;
        //  return $this->ticket;
    }

    /**
     * 获取二维码ticket 临时
     * @return string
     */
    public function getTickets()
    {
        if (null != $this->ticket)
            return $this->ticket;

        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token=' . $this->accessToken;
        $data = [
            // 'expire_seconds' => 2592000,//30天，临时二维码最长有效时间
            'expire_seconds' => 604800,//七天
            'action_name' => 'QR_STR_SCENE',//临时型二维码
            'action_info' => [
                'scene' => [
                    // 'scene_id' => $this->id
                    'scene_str' => $this->scene_str
                ]
            ]
        ];
        $obj = self::post($url, $data);
        return $obj;
    }


    /**
     * 根据ticket生成二维码链接
     * @param  string $ticket
     * @return string
     */
    public function getCodeUrl()
    {
        if (null == $this->ticket)
            $this->getTicket();

        return 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . urlencode($this->ticket);
    }

    /**
     * 发送https的get请求
     * @param  string $url
     * @return obj
     */
    public static function get($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $output = curl_exec($curl);
        curl_close($curl);
        return json_decode($output);
    }

    /**
     * 发送https的POST请求
     * @param  string $url
     * @param  mix $data json | array
     * @return obj
     */
    public static function post($url, $data = null)
    {
        if (is_array($data))
            $data = json_encode($data);

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        if (null != $data) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        $output = curl_exec($curl);
        curl_close($curl);
        return json_decode($output);
    }


}