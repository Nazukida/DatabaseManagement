<?php
require_once 'database.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isUserLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $debug_start = microtime(true);
    $userId = getCurrentUserId();
    $orderId = $_POST['order_id'] ?? 0;
    $rating = $_POST['rating'] ?? 5;
    $comment = $_POST['comment'] ?? '';
    
    // Validate inputs
    if (!$orderId || $rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit();
    }
    
    // Check if order belongs to user and is delivered
    $checkSql = "SELECT OrderID, RestaurantID FROM `order` WHERE OrderID = ? AND UserID = ? AND OrderStatus = 'delivered'";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $orderId, $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found or not delivered']);
        exit();
    }
    
    $order = $checkResult->fetch_assoc();
    $restaurantId = $order['RestaurantID'];
    
    // Check if already reviewed
    $reviewCheckSql = "SELECT ReviewID FROM review WHERE OrderID = ?";
    $reviewCheckStmt = $conn->prepare($reviewCheckSql);
    $reviewCheckStmt->bind_param("i", $orderId);
    $reviewCheckStmt->execute();
    
    if ($reviewCheckStmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Order already reviewed']);
        exit();
    }
    
    // Insert review
    $reviewDate = date('Y-m-d H:i:s');
    $insertSql = "INSERT INTO review (UserID, RestaurantID, OrderID, Rating, Comment, ReviewDate) VALUES (?, ?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertSql);
    $insertStmt->bind_param("iiisss", $userId, $restaurantId, $orderId, $rating, $comment, $reviewDate);
    
    if ($insertStmt->execute()) {
        // Update restaurant average rating
        $avgSql = "SELECT AVG(Rating) as avg_rating FROM review WHERE RestaurantID = ?";
        $avgStmt = $conn->prepare($avgSql);
        $avgStmt->bind_param("i", $restaurantId);
        $avgStmt->execute();
        $avgResult = $avgStmt->get_result();
        $avgRow = $avgResult->fetch_assoc();
        $newAvg = $avgRow['avg_rating'];
        
        $updateRestSql = "UPDATE restaurants SET AverageRating = ? WHERE RestaurantID = ?";
        $updateRestStmt = $conn->prepare($updateRestSql);
        $updateRestStmt->bind_param("di", $newAvg, $restaurantId);
        $updateRestStmt->execute();
        
        $duration = number_format(microtime(true) - $debug_start, 4);
        echo json_encode(['success' => true, 'message' => 'Review submitted successfully', 'query_time' => $duration]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
}
?>
