<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost";
$dbname = "dbms";

// Default to public user
$username = "app_public";
$password = "PublicPass123!";

if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'customer':
            $username = "app_customer";
            $password = "CustomerPass123!";
            break;
        case 'merchant':
            $username = "app_merchant";
            $password = "MerchantPass123!";
            break;
        case 'rider':
            $username = "app_rider";
            $password = "RiderPass123!";
            break;
        case 'admin':
            $username = "app_admin";
            $password = "AdminPass123!";
            break;
    }
}

// 暂时关闭错误报告以防止 HTML 警告破坏 JSON
$driver = new mysqli_driver();
$driver->report_mode = MYSQLI_REPORT_OFF;

// 使用 @ 符号抑制连接错误的直接输出
$conn = @new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // 如果是 API 请求（通过检查请求头或脚本名），返回 JSON 错误
    $isApi = strpos($_SERVER['SCRIPT_NAME'], '_api.php') !== false || 
             (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    if ($isApi) {
        header("Content-Type: application/json");
        echo json_encode([
            "success" => false,
            "message" => "Database connection failed: " . $conn->connect_error
        ]);
    } else {
        // 普通页面则直接显示文本错误
        echo "Database connection failed: " . $conn->connect_error;
    }
    exit();
}

// Ensure UTF-8 encoding for all database interactions
$conn->set_charset("utf8mb4");
?>