<?php
// php/db.php

// 数据库配置
$host = 'localhost';
$username = 'root';      // 本地默认账号
$password = '';          // 本地默认密码为空
$db_name = 'dbms';

// 创建连接 (使用简单的 mysqli 方式)
$conn = mysqli_connect($host, $username, $password, $db_name);

// 检查连接是否成功
if (!$conn) {
    // 如果连接失败，返回错误信息
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode([
        "success" => false, 
        "message" => "Database Connection Error: " . mysqli_connect_error()
    ]);
    exit();
}

// 设置字符集为 utf8mb4 (防止中文乱码)
mysqli_set_charset($conn, "utf8mb4");
?>
