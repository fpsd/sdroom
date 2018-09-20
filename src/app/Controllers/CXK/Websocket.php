<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/14
 * Time: 11:24
 */

namespace app\Controllers\CXK;


class Websocket extends CXK
{
    public function initialization($controller_name, $method_name)
    {
        parent::initialization($controller_name, $method_name);
    }

    /**
     * @throws \Exception
     */
    public function onConnect()
    {
        $uid = time();
        $this->bindUid($uid);
        // 欢迎语句
        $this->sendToUid($uid, [
            'from' => '0',
            'id' => $uid,
            'msg' => "欢迎来到聊天室，你的uid是：" . $uid,
            "users" => yield $this->getAllUids(false),
            "type"=>"onConnect"
        ], false);
        yield $this->sendToOtherIds(
            [
                'from' => '0',
                'id' => $uid,
                "msg" => "from" . $uid . " 加入了聊天室",
                "users"=>yield $this->getAllUids(false),
                "type"=>"onConnect"
            ]
        );
    }

    public function login()
    {

    }

    /**
     * @throws \Server\CoreBase\SwooleException
     */
    public function update()
    {
        $this->sendToAll(
            [
                'type' => 'update',
                'id' => $this->uid,
                'authorized' => false,
            ]);
    }

    /**
     * @throws \Server\CoreBase\SwooleException
     */
    public function message()
    {
        yield $this->sendToOtherIds([
                'from' => $this->uid,
                'id' => $this->uid,
                "msg" => $this->client_data->message
            ]
        );
    }

    public function onClose()
    {
        yield $this->sendToOtherIds(
            [
                'from' => '0',
                'id' => $this->uid,
                "msg" => "from" . $this->uid . " 退出了聊天室"
            ]
        );
    }

    /**
     * 给其他人发
     * @param $data
     * @param bool $destroy
     * @throws \Server\CoreBase\SwooleException
     */
    public function sendToOtherIds($data, $destroy = true)
    {
        // 当前广播里所以uid
        $uids = yield $this->getAllUids(false);
        // 去掉当前uid
        $ids = [];
        foreach ($uids as $id) {
            if ($id != $this->uid) {
                array_push($ids, $id);
            }
        }
        if (count($ids)) {
            $this->sendToUids($ids, $data, $destroy);
        }
    }
}