<?php
/**
 * Created by FPHD.COM.
 * User: linmaogan
 * Date: 2017/12/9
 * Time: 下午10:43
 */

namespace app\Library\WeChat;

class WeChat
{
    private $config;
    private $debug = true;

    function __construct($config = [])
    {
        if (!empty($config)) {
            $this->ctrl = $config[0];
            $this->postObj = $config[1];
            $CreateTime = time();
            $this->CreateTime = $CreateTime;

            $this->xmlHeader = <<<xml
<ToUserName><![CDATA[{$this->postObj->FromUserName}]]></ToUserName>
<FromUserName><![CDATA[{$this->postObj->ToUserName}]]></FromUserName>
<CreateTime>{$CreateTime}</CreateTime>
xml;
        }
    }

    /**
     * 配置变量：
     *  $signature 微信加密签名，signature结合了开发者填写的token参数和请求中的timestamp参数、nonce参数。
     *  $timestamp 时间戳
     *  $nonce 随机数
     *  $channelId 平台id
     * @param array $configs
     */
    public function setConfig($configs = [])
    {
        if (!is_array($configs)) {
            throw new BaseException("微信配置数组不能为空");
        }
        $this->config = $configs;
    }

    /**
     * 验证消息的确来自微信服务器
     * @return bool|null
     */
    public function checkSignature()
    {
        $token = yield api('Extend.WeChat')->getConfigByChannelId($this->config['channelId']);

        $tempArray = array($token['token'], $this->config['timestamp'], $this->config['nonce']);
        sort($tempArray, SORT_STRING);
        $tempString = implode($tempArray);
        $tempString = sha1($tempString);

        if ($this->debug || ($tempString == $this->config['signature'])) {
            return true;
        } else {
            return false;
        }
    }

    public function replayText($content)
    {
        $result = <<<xml
<xml>
{$this->xmlHeader}
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[{$content}]]></Content>
</xml>
xml;
        return $result;
    }

    public function replayNews($Title, $Description, $Url, $PicUrl = '')
    {
        $result = <<<xml
<xml>
{$this->xmlHeader}
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>1</ArticleCount>
<Articles>
<item>
<Title><![CDATA[$Title]]></Title> 
<Description><![CDATA[$Description]]></Description>
<PicUrl><![CDATA[$PicUrl]]></PicUrl>
<Url><![CDATA[$Url]]></Url>
</item>
</Articles>
</xml>
xml;
        return $result;
    }

    public function replayNews2($Title, $Description, $Url, $PicUrl = '')
    {
        $result = <<<xml
<xml>
{$this->xmlHeader}
<MsgType><![CDATA[news]]></MsgType>
<ArticleCount>2</ArticleCount>
<Articles>
<item>
<Title><![CDATA[$Title]]></Title> 
<Description><![CDATA[]]></Description>
<PicUrl><![CDATA[$PicUrl]]></PicUrl>
<Url><![CDATA[$Url]]></Url>
</item>
<item>
<Title><![CDATA[$Description]]></Title> 
<Description><![CDATA[]]></Description>
<PicUrl><![CDATA[]]></PicUrl>
<Url><![CDATA[$Url]]></Url>
</item>
</Articles>
</xml>
xml;
        return $result;
    }

    public function replyNewsMore($newsArray)
    {
        if (!is_array($newsArray)) {
            return;
        }
        $itemTpl = "<item>
                        <Title><![CDATA[%s]]></Title>
                        <Description><![CDATA[%s]]></Description>
                        <PicUrl><![CDATA[%s]]></PicUrl>
                        <Url><![CDATA[%s]]></Url>
                    </item>";
        $item_str = "";
        foreach ($newsArray as $item) {
            $item_str .= sprintf($itemTpl, $item['title'], $item['description'], $item['picurl'], $item['url']);
        }
        $xmlTpl = "<xml>
                    {$this->xmlHeader}
                    <MsgType><![CDATA[news]]></MsgType>
                    <ArticleCount>%s</ArticleCount>
                    <Articles>
                    $item_str</Articles>
                    </xml>";

        $result = sprintf($xmlTpl, count($newsArray));
        return $result;
    }

    /*
     *  发送图片信息给用户
     */
    function replyImage($media_id)
    {
        $result = <<<xml
        <xml>
{$this->xmlHeader}
<MsgType><![CDATA[image]]></MsgType>
<Image>
<MediaId><![CDATA[$media_id]]></MediaId>
</Image>
</xml>
xml;
        return $result;
    }
}