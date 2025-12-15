<?php
// php/login_handler.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

file_put_contents('debug_login.txt', date('Y-m-d H:i:s') . " - Input: " . $inputJSON . "\n", FILE_APPEND);

$roleType = isset($_GET['role']) ? $_GET['role'] : '';

if (!$input || !$roleType) {
    echo json_encode(["success" => false, "message" => "Invalid input or missing role"]);
    exit();
}

require_once 'db.php';

$sql = "";
$identifier = ""; 
$inputPassword = $input['password'] ?? '';

switch ($roleType) {
    case 'customer':
        $identifier = $input['username'] ?? '';
        $safeId = mysqli_real_escape_string($conn, $identifier);
        $safePass = mysqli_real_escape_string($conn, $inputPassword);
        $sql = "SELECT * FROM users WHERE Username = '$safeId' AND PasswordHash = SHA2('$safePass', 256)";
        break;

    case 'merchant':
        $identifier = $input['restaurantId'] ?? '';
        $safeId = mysqli_real_escape_string($conn, $identifier);
        $safePass = mysqli_real_escape_string($conn, $inputPassword);
        $sql = "SELECT * FROM restaurants WHERE RestaurantID = '$safeId' AND PasswordHash = SHA2('$safePass', 256)";
        break;

    case 'rider':
        $identifier = $input['riderId'] ?? '';
        $safeId = mysqli_real_escape_string($conn, $identifier);
        $safePass = mysqli_real_escape_string($conn, $inputPassword);
        $sql = "SELECT * FROM riders WHERE RiderID = '$safeId' AND PasswordHash = SHA2('$safePass', 256)";
        break;

    case 'admin':
        $identifier = $input['username'] ?? '';
        $safeId = mysqli_real_escape_string($conn, $identifier);
        $safePass = mysqli_real_escape_string($conn, $inputPassword);
        $sql = "SELECT * FROM admin WHERE Username = '$safeId' AND PasswordHash = SHA2('$safePass', 256)";
        break;

    default:
        echo json_encode(["success" => false, "message" => "Unknown role type"]);
        exit();
}

// Execute Query and Verify
try {
    $result = mysqli_query($conn, $sql);

    if ($result) {
        $user = mysqli_fetch_assoc($result);

        if ($user) {
            // Security: Remove password hash before sending to frontend
            unset($user['PasswordHash']);
            // Remove binary profile picture data to prevent json_encode failure
            if (isset($user['profile_picture'])) {
                unset($user['profile_picture']);
            }
            
            // Get permission level (default to 1 if not set)
            $level = $user['permission_level'] ?? 1;

            // Set Session for Security
            $_SESSION['role'] = $roleType;
            
            // Determine ID field based on role
            $idField = 'UserID';
            if ($roleType == 'merchant') $idField = 'RestaurantID';
            if ($roleType == 'rider') $idField = 'RiderID';
            if ($roleType == 'admin') $idField = 'AdminID';
            
            $_SESSION['user_id'] = $user[$idField] ?? null;

            echo json_encode([
                "success" => true,
                "message" => "Login Successful",
                "role" => $roleType,
                "user" => $user,
                "permission_level" => $level
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Invalid credentials"]);
        }
    } else {
        // This block catches "Table 'admin' command denied to user..." errors.
        echo json_encode(["success" => false, "message" => "Security Alert: Access Denied to this table."]);
    }

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Server Error"]);
}
?>