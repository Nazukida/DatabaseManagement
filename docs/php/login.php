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

// SECURITY STRATEGY: Application-Level Control
// Principle: Use a unified 'root' account to connect to the database, relying on the switch statement below to isolate permissions.
// As long as the code logic is correct, customers cannot execute administrator queries.
require_once 'db.php';

function performLogin($conn, $table, $idField, $idValue, $password, $roleName) {
    // Prevent SQL Injection: Escape input
    $safeId = mysqli_real_escape_string($conn, $idValue);
    $safePass = mysqli_real_escape_string($conn, $password);

    // Construct SQL query
    $sql = "SELECT * FROM $table WHERE $idField = '$safeId' AND PasswordHash = SHA2('$safePass', 256)";
    
    // Execute query
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        // Fetch one row of data
        $user = mysqli_fetch_assoc($result);
        
        if ($user) {
            // Remove sensitive information
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
