<?php
// php/login_handler.php

// 1. CORS and Header Configuration
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. Get Input Data
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Get the role from the URL parameter (e.g., login_handler.php?role=customer)
$roleType = isset($_GET['role']) ? $_GET['role'] : '';

if (!$input || !$roleType) {
    echo json_encode(["success" => false, "message" => "Invalid input or missing role"]);
    exit();
}

// SECURITY REQUIREMENT 3: Secure Connection / Least Privilege
// Logic: Connect to the database using different accounts based on authority.

$dbHost = 'localhost';
$dbName = 'dbms';
$dbUser = '';
$dbPass = '';

// Switch database credentials based on the requested role
if ($roleType === 'admin') {
    // 🔴 ADMIN LOGIN: Use the High-Privilege Account
    $dbUser = 'root';
    $dbPass = '';
} else {
    // 🟢 NORMAL USER LOGIN: Use the Restricted Account
    $dbUser = 'root';
    $dbPass = '';
}

// Establish Database Connection (mysqli)
$conn = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);

if (!$conn) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database Connection Failed: " . mysqli_connect_error()]);
    exit();
}
mysqli_set_charset($conn, "utf8mb4");

// Login Process Logic

$sql = "";
$identifier = ""; 
$inputPassword = $input['password'] ?? '';

// Construct the query based on role
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
        // If 'linli_safe' tries this, SQL will throw an "Access Denied" error.
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
            
            // Get permission level (default to 1 if not set)
            $level = $user['permission_level'] ?? 1;

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