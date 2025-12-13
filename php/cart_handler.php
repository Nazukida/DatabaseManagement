<?php
require_once 'database.php';
require_once 'functions.php';

session_start();
header('Content-Type: application/json');

if (!isUserLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

$action = $_POST['action'] ?? '';
$userId = getCurrentUserId();

switch ($action) {
    case 'add_to_cart':
        $menuItemId = $_POST['menu_item_id'] ?? 0;
        $restaurantId = $_POST['restaurant_id'] ?? 0;
        $quantity = 1;
        
        // 检查是否已存在pending订单
        $sql = "SELECT o.OrderID 
                FROM `order` o 
                WHERE o.UserID = ? 
                  AND o.RestaurantID = ? 
                  AND o.OrderStatus = 'pending'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $restaurantId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // 已有pending订单，添加到订单项
            $order = $result->fetch_assoc();
            $orderId = $order['OrderID'];
            
            // 检查是否已存在该商品
            $checkSql = "SELECT * FROM order_items WHERE OrderID = ? AND MenuItemID = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("ii", $orderId, $menuItemId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                // 更新数量
                $updateSql = "UPDATE order_items SET Quantity = Quantity + 1 WHERE OrderID = ? AND MenuItemID = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("ii", $orderId, $menuItemId);
                $updateStmt->execute();
            } else {
                // 获取商品价格
                $priceSql = "SELECT Price FROM menu_items WHERE MenuItemID = ?";
                $priceStmt = $conn->prepare($priceSql);
                $priceStmt->bind_param("i", $menuItemId);
                $priceStmt->execute();
                $priceResult = $priceStmt->get_result();
                $priceRow = $priceResult->fetch_assoc();
                $unitPrice = $priceRow['Price'];
                
                // 插入新订单项
                $insertSql = "INSERT INTO order_items (OrderID, MenuItemID, Quantity, UnitPrice) VALUES (?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bind_param("iiid", $orderId, $menuItemId, $quantity, $unitPrice);
                $insertStmt->execute();
            }
            
            // 更新订单总金额
            updateOrderTotal($conn, $orderId);
            
        } else {
            // 创建新订单
            $orderTime = date('Y-m-d H:i:s');
            $deliveryFee = 5.00; // 默认配送费
            
            $insertOrderSql = "INSERT INTO `order` (UserID, RestaurantID, OrderTime, OrderStatus, DeliveryFee) 
                              VALUES (?, ?, ?, 'pending', ?)";
            $insertOrderStmt = $conn->prepare($insertOrderSql);
            $insertOrderStmt->bind_param("iisd", $userId, $restaurantId, $orderTime, $deliveryFee);
            $insertOrderStmt->execute();
            $orderId = $conn->insert_id;
            
            // 获取商品价格
            $priceSql = "SELECT Price FROM menu_items WHERE MenuItemID = ?";
            $priceStmt = $conn->prepare($priceSql);
            $priceStmt->bind_param("i", $menuItemId);
            $priceStmt->execute();
            $priceResult = $priceStmt->get_result();
            $priceRow = $priceResult->fetch_assoc();
            $unitPrice = $priceRow['Price'];
            
            // 添加订单项
            $insertItemSql = "INSERT INTO order_items (OrderID, MenuItemID, Quantity, UnitPrice) VALUES (?, ?, ?, ?)";
            $insertItemStmt = $conn->prepare($insertItemSql);
            $insertItemStmt->bind_param("iiid", $orderId, $menuItemId, $quantity, $unitPrice);
            $insertItemStmt->execute();
            
            // 更新订单总金额
            updateOrderTotal($conn, $orderId);
        }
        
        echo json_encode(['success' => true]);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
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