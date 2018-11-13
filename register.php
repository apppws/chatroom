<?php

    $pdo = new \PDO('mysql:host=127.0.0.1;dbname=chat', 'root', '');
    $pdo->exec('SET NAMES utf8');
    $username = $_POST['username'];
    $password = md5($_POST['password']);
    $sql = "insert into user(username,password) values('$username','$password')";
    $c = $pdo->exec($sql);
    // var_dump($c);
    if($c){

        echo "<script>alert('您已注册成功，返回登录');location='index.html'</script>";
        exit;
       
       }


