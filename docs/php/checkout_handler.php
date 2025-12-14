<?php
// php/checkout_handler.php
require_once 'db.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function log_debug($message) {
    file_put_contents(__DIR__ . "/debug_log.txt", "[" . date('Y-m-d H:i:s') . "] " . $message . "\n", FILE_APPEND);
}

try {
    $input = file_get_contents("php://input");
    log_debug("Received checkout request: " . $input);

    $data = json_decode($input, true);
    if (!$data) {
        throw new Exception("Invalid JSON received");
    }

    if (empty($data['userId']) || empty($data['cart'])) {
        throw new Exception("Missing required fields: userId or cart");
    }

    $userId = intval($data['userId']);
    $cart = $data['cart'];
    $paymentMethod = $data['paymentMethod'] ?? 'Alipay';
    $addressId = 1; 

    $ordersCreated = [];
    
    $conn->begin_transaction();

    foreach ($cart as $restId => $restData) {
        if (empty($restData['items'])) continue;

        $restaurantId = intval($restId);
        $items = $restData['items'];
        
        $totalAmount = 0;
        foreach ($items as $item) {
            $totalAmount += floatval($item['price']) * intval($item['count']);
        }
        $deliveryFee = 5.00;
        $finalAmount = $totalAmount + $deliveryFee;

        $result = $conn->query("SELECT MAX(OrderID) as max_id FROM `order`");
        $row = $result->fetch_assoc();
        $newOrderId = ($row['max_id'] ?? 0) + 1;
        
    $orderTime = date('Y-m-d H:i:s');
    $status = 'confirmed'; 
    $riderId = null;

    $stmt = $conn->prepare("INSERT INTO `order` (OrderID, UserID, RestaurantID, AddressID, TotalAmount, DeliveryFee, OrderStatus, OrderTime, PaymentMethod, RiderID) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiiddsssi", $newOrderId, $userId, $restaurantId, $addressId, $totalAmount, $deliveryFee, $status, $orderTime, $paymentMethod, $riderId);
    $stmt->execute();
    $stmt->close();        
        $stmtItem = $conn->prepare("INSERT INTO order_items (OrderID, MenuItemID, Quantity, UnitPrice) VALUES (?, ?, ?, ?)");
        foreach ($items as $item) {
            $menuItemId = intval($item['menuItemId']);
            $qty = intval($item['count']);
            $price = floatval($item['price']);
            $stmtItem->bind_param("iiid", $newOrderId, $menuItemId, $qty, $price);
            $stmtItem->execute();
        }
        $stmtItem->close();

        $ordersCreated[] = $newOrderId;
        log_debug("Order $newOrderId created successfully.");
    }

    $conn->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Orders placed successfully', 
        'orders' => $ordersCreated
    ]);

} catch (Exception $e) {
    // Rollback on error
    if (isset($conn) && $conn->connect_errno === 0) {
        $conn->rollback();
    }
    log_debug("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
}
?>