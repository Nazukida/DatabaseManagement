<?php
ini_set('display_errors', 0);
require_once "database.php";
require_once "functions.php";

// session_start() is handled in db.php included via database.php
header("Content-Type: application/json");

if (!isUserLoggedIn()) {
    echo json_encode(["success" => false, "message" => "Please login first"]);
    exit();
}

$action = $_POST["action"] ?? "";
$userId = getCurrentUserId();
$debug_start = microtime(true);

switch ($action) {
    case "get_cart":
        $sql = "
        SELECT 
            o.OrderID,
            o.RestaurantID,
            r.RestaurantName,
            o.TotalAmount,
            mi.MenuItemID,
            mi.ItemName,
            mi.Price,
            oi.Quantity,
            (oi.Quantity * oi.UnitPrice) as subtotal
        FROM `order` o
        JOIN restaurants r ON o.RestaurantID = r.RestaurantID
        JOIN order_items oi ON o.OrderID = oi.OrderID
        JOIN menu_items mi ON oi.MenuItemID = mi.MenuItemID
        WHERE o.UserID = ? 
          AND o.OrderStatus = 'pending'
        ";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $cartData = [];
        while($row = $result->fetch_assoc()) {
            $restId = $row['RestaurantID'];
            if (!isset($cartData[$restId])) {
                $cartData[$restId] = [
                    'restaurantName' => $row['RestaurantName'],
                    'items' => []
                ];
            }
            
            $found = false;
            foreach($cartData[$restId]['items'] as &$item) {
                if ($item['menuItemId'] == $row['MenuItemID']) {
                    $item['count'] += $row['Quantity'];
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $cartData[$restId]['items'][] = [
                    'menuItemId' => $row['MenuItemID'],
                    'name' => $row['ItemName'],
                    'price' => (float)$row['Price'],
                    'count' => $row['Quantity']
                ];
            }
        }
        
        $duration = number_format(microtime(true) - $debug_start, 4);
        echo json_encode(["success" => true, "cart" => $cartData, "query_time" => $duration]);
        break;

    case "add_to_cart":
        $menuItemId = $_POST["menu_item_id"] ?? 0;
        $restaurantId = $_POST["restaurant_id"] ?? 0;
        $quantity = 1;
        
        // Check for pending order
        $sql = "SELECT o.OrderID 
                FROM `order` o 
                WHERE o.UserID = ? 
                  AND o.RestaurantID = ? 
                  AND o.OrderStatus = \"pending\"";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $restaurantId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Use existing pending order
            $order = $result->fetch_assoc();
            $orderId = $order["OrderID"];
            
            // Check if item exists
            $checkSql = "SELECT * FROM order_items WHERE OrderID = ? AND MenuItemID = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("ii", $orderId, $menuItemId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                // Update quantity
                $updateSql = "UPDATE order_items SET Quantity = Quantity + 1 WHERE OrderID = ? AND MenuItemID = ?";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bind_param("ii", $orderId, $menuItemId);
                $updateStmt->execute();
            } else {
                // Get price
                $priceSql = "SELECT Price FROM menu_items WHERE MenuItemID = ?";
                $priceStmt = $conn->prepare($priceSql);
                $priceStmt->bind_param("i", $menuItemId);
                $priceStmt->execute();
                $priceResult = $priceStmt->get_result();
                $priceRow = $priceResult->fetch_assoc();
                $unitPrice = $priceRow["Price"];
                
                // Insert item
                $insertSql = "INSERT INTO order_items (OrderID, MenuItemID, Quantity, UnitPrice) VALUES (?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bind_param("iiid", $orderId, $menuItemId, $quantity, $unitPrice);
                $insertStmt->execute();
            }
            
            // Update total
            updateOrderTotal($conn, $orderId);
            
        } else {
            // Create new order
            $orderTime = date("Y-m-d H:i:s");
            $deliveryFee = 5.00; // Default fee
            
            $insertOrderSql = "INSERT INTO `order` (UserID, RestaurantID, OrderTime, OrderStatus, DeliveryFee) 
                              VALUES (?, ?, ?, \"pending\", ?)";
            $insertOrderStmt = $conn->prepare($insertOrderSql);
            $insertOrderStmt->bind_param("iisd", $userId, $restaurantId, $orderTime, $deliveryFee);
            if (!$insertOrderStmt->execute()) {
                echo json_encode(["success" => false, "message" => "Order creation failed: " . $insertOrderStmt->error]);
                exit();
            }
            $orderId = $conn->insert_id;

            if (!$orderId) {
                echo json_encode(["success" => false, "message" => "获取订单ID失败 (Failed to get Order ID)"]);
                exit();
            }
            
            // Get price
            $priceSql = "SELECT Price FROM menu_items WHERE MenuItemID = ?";
            $priceStmt = $conn->prepare($priceSql);
            $priceStmt->bind_param("i", $menuItemId);
            $priceStmt->execute();
            $priceResult = $priceStmt->get_result();
            $priceRow = $priceResult->fetch_assoc();
            $unitPrice = $priceRow["Price"];
            
            // Insert item
            $insertItemSql = "INSERT INTO order_items (OrderID, MenuItemID, Quantity, UnitPrice) VALUES (?, ?, ?, ?)";
            $insertItemStmt = $conn->prepare($insertItemSql);
            $insertItemStmt->bind_param("iiid", $orderId, $menuItemId, $quantity, $unitPrice);
            $insertItemStmt->execute();
            
            // Update total
            updateOrderTotal($conn, $orderId);
        }
        
        $duration = number_format(microtime(true) - $debug_start, 4);
        echo json_encode(["success" => true, "query_time" => $duration]);
        break;

    case "remove_order":
        $orderId = $_POST["order_id"] ?? 0;
        
        // Verify order belongs to user and is pending
        $checkSql = "SELECT OrderID FROM `order` WHERE OrderID = ? AND UserID = ? AND OrderStatus = \"pending\"";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("ii", $orderId, $userId);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0) {
            // Delete order items first
            $deleteItems = "DELETE FROM order_items WHERE OrderID = ?";
            $stmt = $conn->prepare($deleteItems);
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            
            // Delete order
            $deleteOrder = "DELETE FROM `order` WHERE OrderID = ?";
            $stmt = $conn->prepare($deleteOrder);
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            
            $duration = number_format(microtime(true) - $debug_start, 4);
            echo json_encode(["success" => true, "query_time" => $duration]);
        } else {
            echo json_encode(["success" => false, "message" => "Order not found or cannot be removed"]);
        }
        break;
    
    default:
        echo json_encode(["success" => false, "message" => "Invalid action"]);
}
?>
