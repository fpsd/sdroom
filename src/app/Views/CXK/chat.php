<!DOCTYPE html>
<html>
<head>
    <title><?=$this->e($title)?></title>
    <meta charset="utf-8">

    <link rel="stylesheet" href="/css/reset.css"/>
    <link rel="stylesheet" href="/css/bootstrap.css"/>
    <link rel="stylesheet" href="/css/app.css"/>
</head>
<body>
<div class="wrapper">
    <div class="content" id="chat">
        <ul id="chat_conatiner" style="width: 80%;float: left">
        </ul>
        <ul id="chat_user" style="width: 20%;background-color: #d0bcf1;;float: left;height: 100%">
        </ul>
    </div>
    <div class="action">
        <textarea></textarea>
        <button class="btn btn-success" id="clear">清屏</button>
        <button class="btn btn-success" id="send">发送</button>
    </div>
</div>
<script src="http://libs.baidu.com/jquery/2.1.4/jquery.min.js"></script>
<script>
    var ws;
    var controller_name = "CXK/Websocket";
    var users = [];
    $(function () {
        var li = document.createElement('li');
        li.innerHTML = '<span>' + '好友列表' + '</span>';
        document.querySelector('#chat_user').appendChild(li);
        link();
    })
    var addMessage = function (from, msg) {
        var li = document.createElement('li');
        li.innerHTML = '<span>' + from + '</span>' + ' : ' + msg;
        document.querySelector('#chat_conatiner').appendChild(li);
        // 设置内容区的滚动条到底部
        document.querySelector('#chat').scrollTop = document.querySelector('#chat').scrollHeight;

        // 并设置焦点
        document.querySelector('textarea').focus();


    }

    function link() {
        ws = new WebSocket("<?=$this->e($url)?>");//连接服务器
        ws.onopen = function (event) {
            var data = {
                controller_name: controller_name,
                method_name: "onConnect"
            };
            ws.send(JSON.stringify(data));
        };
        ws.onmessage = function (event) {
            var msg = "<p>" + event.data + "</p>";
            msg = JSON.parse(event.data);
            if (msg.from == 0) {
                if (msg.type == "onConnect") {
                    users = msg.users;
                    $("#chat_user").empty();
                    var li = document.createElement('li');
                    li.innerHTML = '<span>' + '好友列表' + '</span>';
                    document.querySelector('#chat_user').appendChild(li);
                    console.log(users);
                    for (var i = 0; i < users.length; i++) {
                        var li = document.createElement('li');
                        li.innerHTML = '<span>' + users[i] + '</span>';
                        document.querySelector('#chat_user').appendChild(li);
                    }
                }
                addMessage('系统消息', msg.msg);
            } else {
                addMessage("from" + msg.from, msg.msg);
            }
        }
        ws.onclose = function (event) {
            addMessage('系统消息', "已经与服务器断开连接当前连接状态：" + this.readyState);
        };
        ws.onerror = function (event) {
            addMessage('系统消息', "WebSocket异常！");
        };
    }

    $("#send").click(function () {
        var ele_msg = document.querySelector('textarea');
        var msg = ele_msg.value.replace('\r\n', '').trim();
        addMessage('你', msg);
        var data = {
            controller_name: controller_name,
            method_name: "message",
            message: msg
        };
        console.log(JSON.stringify(data))
        ws.send(JSON.stringify(data));
        ele_msg.value = '';
    });
    document.querySelector('#clear').addEventListener('click', function () {
        document.querySelector('#chat_conatiner').innerHTML = '';
    });
</script>
</body>
</html>