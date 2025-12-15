<?php
require_once "database.php";
require_once "functions.php";

if (!isUserLoggedIn()) {
    echo json_encode(["success" => false, "message" => "Please login first"]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $orderId = $_POST["order_id"] ?? 0;
    $userId = getCurrentUserId();
    
    // Check if order belongs to user
    $checkSql = "SELECT o.RestaurantID FROM `order` o WHERE o.OrderID = ? AND o.UserID = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $orderId, $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows == 0) {
        echo json_encode(["success" => false, "message" => "Order not found"]);
        exit();
    }
    
    $orderData = $checkResult->fetch_assoc();
    $restaurantId = $orderData["RestaurantID"];
    
    // Check for existing pending order
    $existingSql = "SELECT OrderID FROM `order` 
                   WHERE UserID = ? 
                   AND RestaurantID = ? 
                   AND OrderStatus = \"pending\"";
    $existingStmt = $conn->prepare($existingSql);
    $existingStmt->bind_param("ii", $userId, $restaurantId);
    $existingStmt->execute();
    $existingResult = $existingStmt->get_result();
    
    if ($existingResult->num_rows > 0) {
        // Use existing pending order
        $existingOrder = $existingResult->fetch_assoc();
        $newOrderId = $existingOrder["OrderID"];
    } else {
        // Create new order
        $orderTime = date("Y-m-d H:i:s");
        $deliveryFee = 5.00;
        
        $insertOrderSql = "INSERT INTO `order` (UserID, RestaurantID, OrderTime, OrderStatus, DeliveryFee) 
                          VALUES (?, ?, ?, \"pending\", ?)";
        $insertOrderStmt = $conn->prepare($insertOrderSql);
        $insertOrderStmt->bind_param("iisd", $userId, $restaurantId, $orderTime, $deliveryFee);
        $insertOrderStmt->execute();
        $newOrderId = $conn->insert_id;
    }
    
    // Get original items
    $itemsSql = "SELECT MenuItemID, Quantity, UnitPrice FROM order_items WHERE OrderID = ?";
    $itemsStmt = $conn->prepare($itemsSql);
    $itemsStmt->bind_param("i", $orderId);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();
    
    while($item = $itemsResult->fetch_assoc()) {
        $menuItemId = $item["MenuItemID"];
        $quantity = $item["Quantity"];
        $unitPrice = $item["UnitPrice"];
        
        // Check if item exists in new order
        $checkItemSql = "SELECT Quantity FROM order_items 
                        WHERE OrderID = ? AND MenuItemID = ?";
        $checkItemStmt = $conn->prepare($checkItemSql);
        $checkItemStmt->bind_param("ii", $newOrderId, $menuItemId);
        $checkItemStmt->execute();
        $checkItemResult = $checkItemStmt->get_result();
        
        if ($checkItemResult->num_rows > 0) {
            // Update quantity
            $updateSql = "UPDATE order_items SET Quantity = Quantity + ? 
                         WHERE OrderID = ? AND MenuItemID = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("iii", $quantity, $newOrderId, $menuItemId);
            $updateStmt->execute();
        } else {
            // Insert item
            $insertSql = "INSERT INTO order_items (OrderID, MenuItemID, Quantity, UnitPrice) 
                         VALUES (?, ?, ?, ?)";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("iiid", $newOrderId, $menuItemId, $quantity, $unitPrice);
            $insertStmt->execute();
        }
    }
    
    // Update total
    updateOrderTotal($conn, $newOrderId);
    
    echo json_encode(["success" => true, "message" => "Items added to cart"]);
}
?>
