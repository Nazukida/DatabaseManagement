<?php
$servername = "localhost";
$username = "mysql";
$password = "123456";
$dbname = "waimai";

// 创建连接
$conn = new mysqli($servername, $username, $password, $dbname);

// 检查连接
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 设置字符集
$conn->set_charset("utf8mb4");

// 开启会话
session_start();
?>