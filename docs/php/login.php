<?php
// php/login.php

// Allow CORS
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents("php://input"));

// Get action from URL parameter (e.g., login.php?action=customer)
$action = isset($_GET['action']) ? $_GET['action'] : '';

if (!$data) {
    echo json_encode(["success" => false, "message" => "No input data provided"]);
    exit();
}

// SECURITY STRATEGY: Application-Level Control (替代方案)
// 原理：使用统一的 'root' 账号连接数据库，依靠下方的 switch 语句来隔离权限。
// 只要代码逻辑正确，顾客就无法执行管理员的查询。
$dbHost = 'localhost';
$dbName = 'dbms';
$dbUser = 'root';      // 使用默认的 root 用户
$dbPass = '';          // 默认密码为空

// Establish Database Connection (mysqli)
$conn = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

if (!$conn) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database Connection Failed: " . mysqli_connect_error()]);
    exit();
}
mysqli_set_charset($conn, "utf8mb4");

function performLogin($conn, $table, $idField, $idValue, $password, $roleName) {
    // 防止 SQL 注入：对输入进行转义
    $safeId = mysqli_real_escape_string($conn, $idValue);
    $safePass = mysqli_real_escape_string($conn, $password);

    // 拼接 SQL 语句 (注意：这里直接拼接字符串，比较直观)
    $sql = "SELECT * FROM $table WHERE $idField = '$safeId' AND PasswordHash = SHA2('$safePass', 256)";
    
    // 执行查询
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        // 获取查询结果的一行数据
        $user = mysqli_fetch_assoc($result);
        
        if ($user) {
            // 移除敏感信息
            unset($user['PasswordHash']);
            
            echo json_encode([
                "success" => true, 
                "message" => "Login Successful", 
                "role" => $roleName,
                "user" => $user
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Invalid credentials"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Query Error: " . mysqli_error($conn)]);
    }
}

switch ($action) {
    case 'customer':
        if (isset($data->username) && isset($data->password)) {
            performLogin($conn, 'users', 'Username', $data->username, $data->password, 'Customer');
        } else {
            echo json_encode(["success" => false, "message" => "Missing username or password"]);
        }
        break;

    case 'merchant':
        if (isset($data->restaurantId) && isset($data->password)) {
            performLogin($conn, 'restaurants', 'RestaurantID', $data->restaurantId, $data->password, 'Merchant');
        } else {
            echo json_encode(["success" => false, "message" => "Missing restaurantId or password"]);
        }
        break;

    case 'rider':
        if (isset($data->riderId) && isset($data->password)) {
            performLogin($conn, 'riders', 'RiderID', $data->riderId, $data->password, 'Rider');
        } else {
            echo json_encode(["success" => false, "message" => "Missing riderId or password"]);
        }
        break;

    case 'admin':
        if (isset($data->username) && isset($data->password)) {
            performLogin($conn, 'admin', 'Username', $data->username, $data->password, 'Admin');
        } else {
            echo json_encode(["success" => false, "message" => "Missing username or password"]);
        }
        break;

    default:
        echo json_encode(["success" => false, "message" => "Invalid action specified"]);
        break;
}

mysqli_close($conn);
?>
