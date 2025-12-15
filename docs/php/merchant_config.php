<?php
// 数据库配置
define('DB_SERVER', 'localhost');      // 数据库服务器地址
define('DB_USERNAME', 'root');         // 数据库用户名
define('DB_PASSWORD', '');             // 数据库密码
define('DB_NAME', 'dbms');             // 数据库名称

// 创建数据库连接
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// 检查数据库连接
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
