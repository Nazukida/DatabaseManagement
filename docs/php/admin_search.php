<?php
// php/admin_search.php
include 'db_models.php';

// 1. CORS and Header Configuration
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 2. Get Input Data
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// Support both GET and POST (JSON or Form Data)
$orderId = '';
$merchantId = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($input) {
        // JSON Input
        $orderId = isset($input['order_id']) ? trim($input['order_id']) : '';
        $merchantId = isset($input['merchant_id']) ? trim($input['merchant_id']) : '';
    } else {
        // Form Data Input
        $orderId = isset($_POST['order_id']) ? trim($_POST['order_id']) : '';
        $merchantId = isset($_POST['merchant_id']) ? trim($_POST['merchant_id']) : '';
    }
} else {
    $orderId = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';
    $merchantId = isset($_GET['merchant_id']) ? trim($_GET['merchant_id']) : '';
}

// 3. Validate Input
list($isValid, $errorMsg) = AdminSearch::validateSearch($orderId, $merchantId);

if (!$isValid) {
    ApiResponse::error($errorMsg);
}

// 4. Perform Search
try {
    $results = AdminSearch::search($orderId, $merchantId);
    ApiResponse::success($results);
} catch (Exception $e) {
    error_log("Search Error: " . $e->getMessage());
    ApiResponse::error("An error occurred while searching.");
}
?>