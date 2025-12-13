@
<?php
// Check if user is logged in
function isUserLoggedIn() {
    return isset($_SESSION["user_id"]);
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION["user_id"] ?? null;
}

// Get current user info
function getCurrentUser($conn) {
    if (!isUserLoggedIn()) return null;
    
    $userId = getCurrentUserId();
    $sql = "SELECT * FROM users WHERE UserID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Safe HTML output
function safeOutput($string) {
    return htmlspecialchars($string ?? "", ENT_QUOTES, "UTF-8");
}

// Format price
function formatPrice($price) {
    return number_format($price, 2);
}

// Update order total amount
function updateOrderTotal($conn, $orderId) {
    // Calculate subtotal
    $sql = "SELECT SUM(oi.Quantity * oi.UnitPrice) as subtotal 
            FROM order_items oi 
            WHERE oi.OrderID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $subtotal = $row["subtotal"] ?? 0;
    
    // Get delivery fee
    $feeSql = "SELECT DeliveryFee FROM `order` WHERE OrderID = ?";
    $feeStmt = $conn->prepare($feeSql);
    $feeStmt->bind_param("i", $orderId);
    $feeStmt->execute();
    $feeResult = $feeStmt->get_result();
    $feeRow = $feeResult->fetch_assoc();
    $deliveryFee = $feeRow["DeliveryFee"] ?? 0;
    
    $totalAmount = $subtotal + $deliveryFee;
    
    // Update total amount
    $updateSql = "UPDATE `order` SET TotalAmount = ?, FinalAmount = ? WHERE OrderID = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("ddi", $totalAmount, $totalAmount, $orderId);
    $updateStmt->execute();
}
?>
@
