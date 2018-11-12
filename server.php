<?php
use Workerman\Worker;
// 引入workerman 自动加载
require_once __DIR__ . '/Workerman-master/Autoloader.php';

//保存所有用户
$allUsers = [];
// 有连接时调用
function connect($connection)
{
    $connection->onWebSocketConnect = function ($connection, $http_header) {
        global $allUsers;
        // 保存当前用户列表到用户列表
        $allUsers[$connection->id] = ['username' => $_GET['username']];
        // 保存当前用户名到当前连接的 $connection 对象上
        $connection->username = $_GET['username'];
        // 给所有客户端发送信息
        sendAll([
            'username' => $connection->username,
            'content' => '进入聊天室',
            'datatime' => date('Y-m-d H:i:m'),
            'allUsers' => $allUsers
        ]);

    };
}

// 当收到数据时调用
function message($connection, $data)
{
    // 转发消息给所有客户端
    sendAll([
        'username' => $connection->username,
        'content' => '离开了聊天室',
        'datatime' => date('Y-m-d H:i:m')
    ]);
}
// 当有客户端断开连接时调用
function close($connection)
{
    global $allUsers; 
    // 从用户列表数组中删除当前退出的用户
    unset($allUsers[$connection->id]);
    // 给所有用户发消息
    sendToAll([
        'username'=>$connection->username,
        'content'=>'离开了聊天室',
        'datetime'=>date('Y-m-d H:i'),
        'allUsers'=>$allUsers
    ]);
}

// 给所有人发信息
function sendAll($data){
    global $worker;
    if(is_array($data)){
        $data = json_encode($data);
    }
    // 循环所有客户端
    foreach($worker->connections as $c){
        $c->send($data);
    }
}

//创建一个workerman 的监听端口号
$worker = new Worker('websocket://0.0.0.0:8484');

//进程 
$worker->count = 1;

// 设置回调函数
$worker->onConnect = 'connect';
$worker->onMessage = 'message';
$worker->onClose = 'close';
// 运行
$worker->runAll();