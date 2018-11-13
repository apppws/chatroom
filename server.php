<?php
use Workerman\Worker;
use Firebase\JWT\JWT;
// 引入workerman 自动加载
require_once __DIR__ . '/Workerman-master/Autoloader.php';
require('./vendor/autoload.php');

//保存所有用户
$allUsers = [];
// 客户端用户数据
$userConn = [];
// 有连接时调用
function connect($connection)
{
    $connection->onWebSocketConnect = function ($connection, $http_header) {
        global $allUsers, $userConn, $worker;

        // 解析jwt令牌
        try {
            $key = 'abcd1234';
            $data = JWT::decode($_GET['token'], $key, array('HS256'));
            // 把id和name保存到这个连接上
            $connection->uid = $data->id;
            $connection->uname = $data->name;
            // 保存当前连接到所有用户的数组中
            $allUsers[$data->id] = $data->name;
            $userConn[$data->id] = $connection;
            // 如果用户连接成功  就通知所有其他客户端有新的客户端连接
            foreach ($worker->connections as $v) {
                $v->send(json_encode([
                    'username' => $data->name,
                    'content' => '加入了聊天室',
                    'datetime' => date('Y-m-d H:i'),
                    'type' => 'users',
                    'users' => $allUsers
                ]));
            }
        } catch (\Firebase\JWT\ExpiredException $e) {
            // 关闭连接
            $connection->close();
        } catch (\Exception $e) {
            // 关闭连接
            $connection->close();
        }

    };
}

// 当收到数据时调用
function message($connection, $data)
{
    // var_dump($data);
    global $worker, $allUsers;
    
    // 从消息中解析出第一个 ：前面的内容  判断是单发还是群发
    // 将字符串转为数组
    $ret = explode(':', $data);
    // 取出第一个元素 并去掉：前面的内容
    $code = $ret[0];
    unset($ret[0]);
    // 再把数组拼成字符串
    $rawData = implode(':', $ret);
    // 判断第一个元素
    if ($code == "all") {
        // 群发
        foreach ($worker->connections as $v) {
            $v->send(json_encode([
                'username' => $connection->uname,
                'content' => $rawData,
                'datetime' => date('Y-m-d H:i'),
                'type' => 'users',
                'users' => $allUsers
            ]));
        }
    } else {
        // 引进保存客户端与对应用户id的数组
        global $userConn;
        $userConn[$code]->send(json_encode([
            'username' => $connection->uname,
            'datetime' => date('Y-m-d H:i'),
            'type' => 'message',
            'content' => $rawData,
            'message' => $rawData
        ]));
    }
}
// 当有客户端断开连接时调用
function close($connection, $data)
{
    global $allUsers, $worker; 
    // 从用户列表数组中删除当前退出的用户
    unset($allUsers[$connection->id]);
    // 给所有用户发消息
    foreach ($worker->connections as $v) {
        $v->send(json_encode([
            'username' => $data->name,
            'content' => '退出了聊天室',
            'datetime' => date('Y-m-d H:i'),
            'type' => 'users',
            'users' => $allUsers
        ]));
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