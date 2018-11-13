<?php 
// 引入自动加载的 扩展包
require('./vendor/autoload.php');
// 生成令牌
use \Firebase\JWT\JWT;

// 连接数据库
$pdo = new \PDO('mysql:host=127.0.0.1;dbname=chat', 'root', '');
$pdo->exec('SET NAMES utf8');
// 接收原始数据
$post = file_get_contents('php://input');
// 把JSON转成数组
$_POST = json_decode($post, TRUE);
// 查询数据库
$stmt = $pdo->prepare("select * from user where username=? and password=?");
$stmt->execute([
    $_POST['username'],
    md5($_POST['password'])
]);
// 取出来所有信息
$user = $stmt->fetch(PDO::FETCH_ASSOC);
// var_dump($user);exit;
if($user)
{
    $key = 'abcd1234';
    $now = time();
    $data = [
        'id' => $user['id'],
        'name' => $user['username'],
    ];
    // var_dump($data);exit;
    // 为这个数据生成令牌
    $jwt = JWT::encode($data, $key);
    // 返回 JSON 数据
    echo json_encode([
        'code'=>'200',
        'jwt'=>$jwt,
    ]);
}
else
{
    // 返回 JSON 数据
    echo json_encode([
        'code'=>'403',
        'error'=>'用户名或者密码错误！',
    ]);
}