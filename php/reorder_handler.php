<?php
require_once 'database.php';
require_once 'functions.php';

if (!isUserLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $orderId = $_POST['order_id'] ?? 0;
    $userId = getCurrentUserId();
    
    // 验证订单是否属于当前用户
    $checkSql = "SELECT o.RestaurantID FROM `order` o WHERE o.OrderID = ? AND o.UserID = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $orderId, $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    $orderData = $checkResult->fetch_assoc();
    $restaurantId = $orderData['RestaurantID'];
    
    // 检查是否已存在该餐厅的pending订单
    $existingSql = "SELECT OrderID FROM `order` 
                   WHERE UserID = ? 
                   AND RestaurantID = ? 
                   AND OrderStatus = 'pending'";
    $existingStmt = $conn->prepare($existingSql);
    $existingStmt->bind_param("ii", $userId, $restaurantId);
    $existingStmt->execute();
    $existingResult = $existingStmt->get_result();
    
    if ($existingResult->num_rows > 0) {
        // 使用现有pending订单
        $existingOrder = $existingResult->fetch_assoc();
        $newOrderId = $existingOrder['OrderID'];
    } else {
        // 创建新订单
        $orderTime = date('Y-m-d H:i:s');
        $deliveryFee = 5.00;
        
        $insertOrderSql = "INSERT INTO `order` (UserID, RestaurantID, OrderTime, OrderStatus, DeliveryFee) 
                          VALUES (?, ?, ?, 'pending', ?)";
        $insertOrderStmt = $conn->prepare($insertOrderSql);
        $insertOrderStmt->bind_param("iisd", $userId, $restaurantId, $orderTime, $deliveryFee);
        $insertOrderStmt->execute();
        $newOrderId = $conn->insert_id;
    }
    
    // 获取原订单的商品
    $itemsSql = "SELECT MenuItemID, Quantity, UnitPrice FROM order_items WHERE OrderID = ?";
    $itemsStmt = $conn->prepare($itemsSql);
    $itemsStmt->bind_param("i", $orderId);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();
    
    while($item = $itemsResult->fetch_assoc()) {
        $menuItemId = $item['MenuItemID'];
        $quantity = $item['Quantity'];
        $unitPrice = $item['UnitPrice'];
        
        // 检查是否已存在该商品
        $checkItemSql = "SELECT Quantity FROM order_items 
                        WHERE OrderID = ? AND MenuItemID = ?";
        $checkItemStmt = $conn->prepare($checkItemSql);
        $checkItemStmt->bind_param("ii", $newOrderId, $menuItemId);
        $checkItemStmt->execute();
        $checkItemResult = $checkItemStmt->get_result();
        
        if ($checkItemResult->num_rows > 0) {
            // 更新数量
            $updateSql = "UPDATE order_items SET Quantity = Quantity + ? 
                         WHERE OrderID = ? AND MenuItemID = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("iii", $quantity, $newOrderId, $menuItemId);
            $updateStmt->execute();
        } else {
            // 插入新商品
            $insertSql = "INSERT INTO order_items (OrderID, MenuItemID, Quantity, UnitPrice) 
                         VALUES (?, ?, ?, ?)";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("iiid", $newOrderId, $menuItemId, $quantity, $unitPrice);
            $insertStmt->execute();
        }
    }
    
    // 更新订单总金额
    updateOrderTotal($conn, $newOrderId);
    
    echo json_encode(['success' => true, 'message' => 'Items added to cart']);
}

function updateOrderTotal($conn, $orderId) {
    // 计算订单总金额
    $sql = "SELECT SUM(oi.Quantity * oi.UnitPrice) as subtotal 
            FROM order_items oi 
            WHERE oi.OrderID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $subtotal = $row['subtotal'] ?? 0;
    
    // 获取配送费
    $feeSql = "SELECT DeliveryFee FROM `order` WHERE OrderID = ?";
    $feeStmt = $conn->prepare($feeSql);
    $feeStmt->bind_param("i", $orderId);
    $feeStmt->execute();
    $feeResult = $feeStmt->get_result();
    $feeRow = $feeResult->fetch_assoc();
    $deliveryFee = $feeRow['DeliveryFee'] ?? 0;
    
    $totalAmount = $subtotal + $deliveryFee;
    
    // 更新订单总金额
    $updateSql = "UPDATE `order` SET TotalAmount = ?, FinalAmount = ? WHERE OrderID = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("ddi", $totalAmount, $totalAmount, $orderId);
    $updateStmt->execute();
}
?>